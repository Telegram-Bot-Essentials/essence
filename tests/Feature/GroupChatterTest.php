<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\TextMatchers\TextMatcher;

/*
 * People just talking in a group are none of the bot's business: nothing is
 * stored about them. Someone only gets a row by using the bot there, as a
 * user with no private chat, which is what tells them apart from the others.
 */

uses(RefreshDatabase::class);

class ChatterGroupCommand extends Command
{
    protected string $name = 'chattergroup';

    protected array $aliases = ['chatteralias'];

    protected ChatScope $chatScope = ChatScope::Groups;

    public static int $runs = 0;

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

class ChatterPrivateCommand extends Command
{
    protected string $name = 'chatterprivate';

    public function handle(): ?bool
    {
        return true;
    }
}

class ChatterGroupMatcher extends TextMatcher
{
    protected ChatScope $chatScope = ChatScope::Groups;

    protected ?string $pattern = '~link~';

    public function handle(): void {}
}

const CHATTER_GROUP = -100666;
const CHATTER = 7101;

function chatterSays(string $text, int $chatId = CHATTER_GROUP, ?int $topicId = null)
{
    return test()->postWebhookUpdate(
        test()->bot,
        test()->inGroupChat(test()->makeMessageUpdate($text, CHATTER), $chatId, 'supergroup', $topicId),
    )->assertOk();
}

function nothingStored(): bool
{
    return ! TelegramUser::where('peer_id', CHATTER)->exists()
        && ! BotUser::where('telegram_user_peer_id', CHATTER)->exists();
}

beforeEach(function () {
    config(['tbe-essence.groups.enabled' => true]);
    commandBus()->addCommand(ChatterGroupCommand::class);
    commandBus()->addCommand(ChatterPrivateCommand::class);
    ChatterGroupCommand::$runs = 0;

    $this->bot = $this->makeBot();
    tenancy()->initialize($this->bot);
    Cache::put('tbe:bot-username:'.$this->bot->getKey().':'.md5((string) $this->bot->bot_token), 'tbe_test_bot', 3600);
});

it('stores nothing about someone just talking', function () {
    chatterSays('hello everyone');

    expect(nothingStored())->toBeTrue();
});

it('stores nothing for a command it would not run in a group', function (string $text) {
    chatterSays($text);

    expect(nothingStored())->toBeTrue();
})->with([
    'for another bot' => ['/chattergroup@other_bot'],
    'private only' => ['/chatterprivate'],
    'unknown' => ['/whatever'],
]);

it('gives someone using the bot a row with no private chat', function (string $text) {
    chatterSays($text);

    expect(ChatterGroupCommand::$runs)->toBe(1)
        ->and(BotUser::where('telegram_user_peer_id', CHATTER)->first()->has_private_chat)->toBeFalse();
})->with([
    'plain' => ['/chattergroup'],
    'addressed to this bot' => ['/chattergroup@tbe_test_bot'],
    'by alias' => ['/chatteralias'],
    'with arguments' => ['/chattergroup one two'],
]);

it('gives someone pressing a button a row', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate('x', CHATTER), CHATTER_GROUP))->assertOk();

    expect(BotUser::where('telegram_user_peer_id', CHATTER)->exists())->toBeTrue();
});

it('leaves a known user just talking untouched', function () {
    $user = $this->makeBotUser($this->bot, CHATTER);
    $user->forceFill(['last_interaction' => now()->subDay()])->save();
    $before = $user->fresh()->last_interaction;

    chatterSays('hello everyone');

    expect(Conversation::where('chat_id', CHATTER_GROUP)->exists())->toBeFalse()
        ->and($user->fresh()->last_interaction->equalTo($before))->toBeTrue();
});

it('still hands a typed answer to an open flow', function () {
    $user = $this->makeBotUser($this->bot, CHATTER);
    $user->conversationIn(CHATTER_GROUP)->changeState('flow');
    $user->forceFill(['last_interaction' => now()->subDay()])->save();
    $before = $user->fresh()->last_interaction;

    chatterSays('3');

    expect($user->fresh()->last_interaction->greaterThan($before))->toBeTrue();
});

it('does not take a flow open elsewhere as a reason to listen', function () {
    $user = $this->makeBotUser($this->bot, CHATTER);
    $user->conversationIn(-100555)->changeState('flow');
    $user->conversationIn(CHATTER_GROUP, 9)->changeState('flow');
    $user->forceFill(['last_interaction' => now()->subDay()])->save();
    $before = $user->fresh()->last_interaction;

    chatterSays('hello everyone');

    expect($user->fresh()->last_interaction->equalTo($before))->toBeTrue();
});

it('finds an open flow in its forum topic', function () {
    $user = $this->makeBotUser($this->bot, CHATTER);
    $user->conversationIn(CHATTER_GROUP, 9)->changeState('flow');
    $user->forceFill(['last_interaction' => now()->subDay()])->save();
    $before = $user->fresh()->last_interaction;

    chatterSays('3', CHATTER_GROUP, 9);

    expect($user->fresh()->last_interaction->greaterThan($before))->toBeTrue();
});

it('still cancels an open flow with a command that only runs privately', function () {
    $user = $this->makeBotUser($this->bot, CHATTER);
    $user->conversationIn(CHATTER_GROUP)->changeState(encodeAnswerState('NOPE', 'answer', []));

    chatterSays('/chatterprivate');

    expect($user->conversationIn(CHATTER_GROUP)->state)->toBeNull();
});

it('hears everyone when a bot has a text matcher for groups', function () {
    textMatcherBus()->addTextMatcher(ChatterGroupMatcher::class);

    chatterSays('hello everyone');

    expect(BotUser::where('telegram_user_peer_id', CHATTER)->exists())->toBeTrue();
});

it('changes nothing in private chats', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hello', CHATTER))->assertOk();

    expect(BotUser::where('telegram_user_peer_id', CHATTER)->first()->has_private_chat)->toBeTrue();
});
