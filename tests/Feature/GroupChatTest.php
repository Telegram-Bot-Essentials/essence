<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageOwner;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member\GroupKeyQuery;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

/*
 * With tbe-essence.groups.enabled on, group updates reach the handlers that
 * opt into groups (ChatScope). Everything else stays private-only and quiet,
 * replies go to the group (and topic), groups never get a reply keyboard,
 * and only the user a group message was sent for can press its buttons.
 * See docs/plans/multi-chat-context.md for the findings behind this.
 */

uses(RefreshDatabase::class);

class GroupOnlyCommand extends Command
{
    protected string $name = 'grouponly';

    protected ChatScope $chatScope = ChatScope::Groups;

    public static int $runs = 0;

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

/** Opens a group flow on a choice step and sends its prompt, buttons included. */
class GroupPromptCommand extends Command
{
    protected string $name = 'groupprompt';

    protected ChatScope $chatScope = ChatScope::Groups;

    public function handle(): ?bool
    {
        wHook()->conversation()->changeState(encodeAnswerState('GROUPFLOW', 'answer', ['step' => 'pick']));
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'Pick one', 'reply_markup' => replyKeyboard()]);

        return true;
    }
}

class PrivateOnlyCommand extends Command
{
    protected string $name = 'privateonly';

    public static int $runs = 0;

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

class GroupFlowAnswer extends StateAnswer
{
    protected string $type = 'GROUPFLOW';

    protected ChatScope $chatScope = ChatScope::Groups;

    /** @var list<string> */
    public static array $answers = [];

    public function getAllowedFields(): array
    {
        return [AllowableFields::TEXT->value];
    }

    public function keyboard(): ?Keyboard
    {
        return ($this->params['step'] ?? null) === 'pick'
            ? Keyboard::make()->row(['Video', 'Audio'])
            : null;
    }

    public function answer(): void
    {
        self::$answers[] = (string) wHook()->update()->message->text;
    }
}

class GroupPingQuery extends CallbackQuery
{
    protected string $type = 'GROUPPING';

    protected ChatScope $chatScope = ChatScope::Groups;

    public static int $pings = 0;

    public function ping(): void
    {
        self::$pings++;
    }
}

const GROUP_ID = -100777;

function sentTo(int $chatId): Closure
{
    return fn ($request) => str_ends_with($request->url(), '/sendMessage') && (int) $request['chat_id'] === $chatId;
}

beforeEach(function () {
    config(['tbe-essence.groups.enabled' => true]);

    commandBus()->addCommand(GroupOnlyCommand::class);
    commandBus()->addCommand(PrivateOnlyCommand::class);
    commandBus()->addCommand(GroupPromptCommand::class);
    stateAnswerBus()->addStateAnswer(GroupFlowAnswer::class);
    callbackQueryBus()->addCallbackQuery(GroupPingQuery::class);
    GroupOnlyCommand::$runs = 0;
    PrivateOnlyCommand::$runs = 0;
    GroupFlowAnswer::$answers = [];
    GroupPingQuery::$pings = 0;

    $this->bot = $this->makeBot();
    // Direct model calls below (conversations, owners) are scoped to a bot.
    tenancy()->initialize($this->bot);
    Cache::put('tbe:bot-username:'.$this->bot->getKey().':'.md5((string) $this->bot->bot_token), 'tbe_test_bot');

    $this->owner = $this->makeBotUser($this->bot, 5001);
    $this->other = $this->makeBotUser($this->bot, 5002);
});

/** The markup as Telegram receives it. */
function markup(Keyboard $keyboard): array
{
    return json_decode(json_encode($keyboard), true);
}

function groupFlow(BotUser $user, string $step): void
{
    $user->conversationIn(GROUP_ID)->changeState(encodeAnswerState('GROUPFLOW', 'answer', ['step' => $step]));
}

it('runs a group handler in a group but not a private-only one', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/grouponly', 5001), GROUP_ID))->assertOk();
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/privateonly', 5001), GROUP_ID))->assertOk();
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/grouponly', 5001))->assertOk();

    expect(GroupOnlyCommand::$runs)->toBe(1)
        ->and(PrivateOnlyCommand::$runs)->toBe(0);
});

it('stays quiet in a group about text no handler claims', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('just chatting', 5001), GROUP_ID))->assertOk();

    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/sendMessage'));
});

it('answers in the group, and in the forum topic', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/help', 5001), GROUP_ID, topicId: 7))->assertOk();

    $this->assertTelegramSent(fn ($request) => sentTo(GROUP_ID)($request) && (int) $request['message_thread_id'] === 7);
    Http::assertNotSent(sentTo(5001));
});

it('takes no topic from a plain reply chain', function () {
    $update = $this->inGroupChat($this->makeMessageUpdate('/help', 5001), GROUP_ID);
    $update['message']['message_thread_id'] = 99;

    $this->postWebhookUpdate($this->bot, $update)->assertOk();

    $this->assertTelegramSent(fn ($request) => sentTo(GROUP_ID)($request) && ! isset($request['message_thread_id']));
});

it('leaves a command for another bot alone, flow included', function () {
    groupFlow($this->owner, 'free');

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/help@some_other_bot', 5001), GROUP_ID))->assertOk();

    Http::assertNotSent(sentTo(GROUP_ID));
    expect($this->owner->conversationIn(GROUP_ID)->state)->not->toBeNull();

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/help@TBE_test_bot', 5001), GROUP_ID))->assertOk();

    $this->assertTelegramSent(sentTo(GROUP_ID));
});

it('drops service messages and messages sent as a chat', function () {
    $joined = $this->inGroupChat($this->makeMessageUpdate('', 5003), GROUP_ID);
    unset($joined['message']['text']);
    $joined['message']['new_chat_members'] = [['id' => 5003, 'is_bot' => false, 'first_name' => 'New']];

    $asChat = $this->inGroupChat($this->makeMessageUpdate('/help', 1087968824), GROUP_ID);
    $asChat['message']['sender_chat'] = ['id' => GROUP_ID, 'type' => 'supergroup', 'title' => 'A group'];

    $this->postWebhookUpdate($this->bot, $joined)->assertOk();
    $this->postWebhookUpdate($this->bot, $asChat)->assertOk();

    expect(BotUser::query()->whereIn('telegram_user_peer_id', [5003, 1087968824])->exists())->toBeFalse();
    Http::assertNothingSent();
});

it('keeps a user\'s private flow apart from their group one', function () {
    $this->owner->privateConversation()->changeState('SOMEFLOW|step');

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/grouponly', 5001), GROUP_ID))->assertOk();

    expect($this->owner->privateConversation()->fresh()->state)->toBe('SOMEFLOW|step');
});

it('takes any message from the user as the answer to their open group flow', function () {
    groupFlow($this->owner, 'free');

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('my answer', 5001), GROUP_ID))->assertOk();
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('not mine to answer', 5002), GROUP_ID))->assertOk();

    expect(GroupFlowAnswer::$answers)->toBe(['my answer']);
});

it('never sends a reply keyboard, or a removal, to a group', function () {
    $idle = markup(replyKeyboard($this->owner->conversationIn(GROUP_ID)));

    expect($idle)->not->toHaveKey('keyboard')
        ->and($idle)->not->toHaveKey('remove_keyboard');

    groupFlow($this->owner, 'free');
    $free = markup(replyKeyboard($this->owner->conversationIn(GROUP_ID)));

    expect($free)->not->toHaveKey('keyboard')
        ->and(array_column($free['inline_keyboard'][0], 'text'))->toBe([__('tbe::cancel_process.reply_key')]);
});

it('turns a flow\'s keys into inline buttons tied to the step', function () {
    groupFlow($this->owner, 'pick');
    $conversation = $this->owner->conversationIn(GROUP_ID);

    $rows = markup(replyKeyboard($conversation))['inline_keyboard'];

    expect(array_merge(...array_map(fn ($row) => array_column($row, 'text'), $rows)))
        ->toBe(['Video', 'Audio', __('tbe::cancel_process.reply_key')])
        ->and($rows[1][0]['callback_data'])->toBe(GroupKeyQuery::callbackData(1, (string) $conversation->state));
});

it('replays a pressed key as the presser\'s typed answer', function () {
    groupFlow($this->owner, 'pick');
    $data = GroupKeyQuery::callbackData(1, (string) $this->owner->conversationIn(GROUP_ID)->state);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate($data, 5001), GROUP_ID))->assertOk();

    expect(GroupFlowAnswer::$answers)->toBe(['Audio']);
    $this->assertTelegramSent(fn ($request) => str_ends_with($request->url(), '/editMessageReplyMarkup'));
});

it('cancels the presser\'s group flow from the inline Cancel button', function () {
    groupFlow($this->owner, 'pick');
    $data = GroupKeyQuery::callbackData(2, (string) $this->owner->conversationIn(GROUP_ID)->state);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate($data, 5001), GROUP_ID))->assertOk();

    expect($this->owner->conversationIn(GROUP_ID)->state)->toBeNull();
    $this->assertTelegramSent(fn ($request) => sentTo(GROUP_ID)($request) && $request['text'] === __('tbe::cancel_process.main.text.response'));
});

it('turns away someone else\'s press on a flow key', function () {
    groupFlow($this->owner, 'pick');
    $data = GroupKeyQuery::callbackData(0, (string) $this->owner->conversationIn(GROUP_ID)->state);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate($data, 5002), GROUP_ID))->assertOk();

    expect(GroupFlowAnswer::$answers)->toBe([])
        ->and($this->owner->conversationIn(GROUP_ID)->state)->not->toBeNull();
    $this->assertTelegramSent(fn ($request) => str_ends_with($request->url(), '/answerCallbackQuery') && $request['show_alert']);
});

it('turns away a press on a step that has moved on', function () {
    groupFlow($this->owner, 'pick');
    $stale = GroupKeyQuery::callbackData(0, (string) $this->owner->conversationIn(GROUP_ID)->state);
    groupFlow($this->owner, 'free');

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate($stale, 5001), GROUP_ID))->assertOk();

    expect(GroupFlowAnswer::$answers)->toBe([]);
});

it('lets only the owner press the buttons of a group message', function () {
    $press = function (int $peerId) {
        $update = $this->inGroupChat($this->makeCallbackQueryUpdate('GROUPPING#ping', $peerId), GROUP_ID);
        $update['callback_query']['message']['message_id'] = 4242;
        $this->postWebhookUpdate($this->bot, $update)->assertOk();
    };
    MessageOwner::record(GROUP_ID, 4242, $this->owner->getKey());

    $press(5002);
    expect(GroupPingQuery::$pings)->toBe(0);

    $press(5001);
    expect(GroupPingQuery::$pings)->toBe(1);
});

it('records who a group message with inline buttons was sent for', function () {
    Http::swap(new HttpFactory);
    Http::fake(['*' => Http::response(['ok' => true, 'result' => [
        'message_id' => 3131, 'date' => 0, 'chat' => ['id' => GROUP_ID, 'type' => 'supergroup'],
    ]])]);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/groupprompt', 5001), GROUP_ID))->assertOk();

    expect(MessageOwner::ownerOf(GROUP_ID, 3131))->toBe($this->owner->getKey());
});

it('does not count a group message as proof the user can be reached privately', function () {
    $this->owner->update(['status' => BotUser::STATUS_BLOCKED]);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/grouponly', 5001), GROUP_ID))->assertOk();

    expect($this->owner->fresh()->status)->toBe(BotUser::STATUS_BLOCKED);
});
