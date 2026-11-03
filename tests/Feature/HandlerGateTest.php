<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Contracts\HandlerGate;
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQuery;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Essence\Tests\Fixtures\RunsForUserStateAnswer;
use TelegramBotEssentials\Essence\Tests\Fixtures\SupportReplyKey;

uses(RefreshDatabase::class);

class RefuseAllButStart implements HandlerGate
{
    /** @var list<class-string> */
    public static array $seen = [];

    public function check(object $handler): void
    {
        self::$seen[] = $handler::class;

        if ($handler instanceof Command && $handler->getName() === 'start') {
            return;
        }

        dependsOn(false, (new TelegramResponse(text: 'gate says no'))->asNewMessage()->inlineQueryButton('Join', 'join'));
    }
}

class PingGateCommand extends Command
{
    protected string $name = 'ping';

    public function handle(): ?bool
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'pong']);

        return true;
    }
}

class StartGateCommand extends Command
{
    protected string $name = 'start';

    public function handle(): ?bool
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'welcome']);

        return true;
    }
}

class GatePressQuery extends CallbackQuery
{
    protected string $type = 'GATE_PRESS';

    public function press(): void
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'pressed']);
    }
}

function gateTexts(): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains((string) $pair[0]->url(), '/sendMessage'))
        ->map(fn ($pair) => (string) $pair[0]['text'])
        ->values()
        ->all();
}

beforeEach(function () {
    RefuseAllButStart::$seen = [];
    handlerGates()->add(RefuseAllButStart::class);
    commandBus()->addCommands([PingGateCommand::class, StartGateCommand::class]);
    $this->bot = $this->makeBot();
});

it('stops a command with the response of the gate', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/ping', 9301))->assertOk();

    expect(gateTexts())->toBe(['gate says no']);
});

it('stops a reply key', function () {
    replyKeyBus()->addReplyKeys([SupportReplyKey::class]);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('Support', 9302))->assertOk();

    expect(RefuseAllButStart::$seen)->toContain(SupportReplyKey::class)
        ->and(gateTexts())->toBe(['gate says no']);
});

it('stops a state answer and keeps the state, so the answer can be sent again', function () {
    stateAnswerBus()->addStateAnswer(RunsForUserStateAnswer::class);
    $state = encodeAnswerState('RUNS_FOR_USER', 'answer');
    $user = $this->makeBotUser($this->bot, 9303);
    $user->privateConversation()->changeState($state);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('go', 9303))->assertOk();

    expect(gateTexts())->toBe(['gate says no'])
        ->and($user->privateConversation()->fresh()->state)->toBe($state);
});

it('keeps the open flow when a command is refused', function () {
    $state = encodeAnswerState('RUNS_FOR_USER', 'answer');
    stateAnswerBus()->addStateAnswer(RunsForUserStateAnswer::class);
    $user = $this->makeBotUser($this->bot, 9304);
    $user->privateConversation()->changeState($state);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/ping', 9304))->assertOk();

    expect($user->privateConversation()->fresh()->state)->toBe($state);
});

it('stops a button press without touching the message it sits on', function () {
    callbackQueryBus()->addCallbackQueries([GatePressQuery::class]);

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('GATE_PRESS', 'press'), 9305))->assertOk();

    expect(gateTexts())->toBe(['gate says no']);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/editMessage'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/answerCallbackQuery'));
});

it('asks nothing of a gate when no handler claims the update', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('just talking', 9306))->assertOk();

    expect(RefuseAllButStart::$seen)->toBe([])
        ->and(gateTexts())->not->toContain('gate says no');
});

it('lets a gate pass /start and still hands its deep link to listeners', function () {
    $links = [];
    Event::listen(BotDeepLinkReceived::class, function (BotDeepLinkReceived $event) use (&$links) {
        $links[] = $event->payload;
    });

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/start c_abc', 9307))->assertOk();

    expect(gateTexts())->toBe(['welcome'])->and($links)->toBe(['c_abc']);
});

it('still hands over the deep link when the welcome message fails', function () {
    $factory = new Factory;
    $factory->fake(['*' => Http::response(['ok' => false, 'error_code' => 500, 'description' => 'Internal Server Error'], 500)]);
    Http::swap($factory);

    $links = [];
    Event::listen(BotDeepLinkReceived::class, function (BotDeepLinkReceived $event) use (&$links) {
        $links[] = $event->payload;
    });

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/start c_abc', 9308));

    expect($links)->toBe(['c_abc']);
});

it('keeps every listener running when one of them fails', function () {
    $ran = [];
    Event::listen(BotUpdateReceived::class, function () {
        throw new RuntimeException('first listener breaks');
    });
    Event::listen(BotUpdateReceived::class, function () use (&$ran) {
        $ran[] = 'second';
    });

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/start', 9309))->assertOk();

    expect($ran)->toBe(['second'])->and(gateTexts())->toContain('welcome');
});

it('lets a listener failure still reach the user when it means to', function () {
    Event::listen(BotUpdateReceived::class, function () {
        dependsOn(false, 'this feature is off');
    });

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/start', 9310))->assertOk();

    expect(gateTexts())->toContain('this feature is off')->toContain('welcome');
});

it('answers an inline query with a button instead of a message', function () {
    inlineQueryBus()->setHandler(new class extends InlineQuery
    {
        public function handle(): void {}
    });

    $this->postWebhookUpdate($this->bot, [
        'inline_query' => [
            'id' => '77',
            'from' => ['id' => 9311, 'is_bot' => false, 'first_name' => 'T'],
            'query' => 'x',
            'offset' => '',
        ],
    ])->assertOk();

    expect(gateTexts())->toBe([]);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/answerInlineQuery')
        && json_decode((string) $request['button'], true) === ['text' => 'Join', 'start_parameter' => 'join']);
});
