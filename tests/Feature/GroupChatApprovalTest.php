<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Events\BotChatJoined;
use TelegramBotEssentials\Essence\Events\BotChatLeft;
use TelegramBotEssentials\Essence\Models\BotChat;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\MessageOwner;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

/*
 * A bot takes payments, so a group has to be approved before it can use it,
 * or a stranger's group could run it under the owner's name. Telegram gives no
 * say in who adds a bot, so the gate is here: until approved, a group's
 * updates are dropped before a BotUser is minted for anyone in it.
 */

uses(RefreshDatabase::class);

class ApprovalGroupCommand extends Command
{
    protected string $name = 'approvalgroup';

    protected ChatScope $chatScope = ChatScope::Groups;

    public static int $runs = 0;

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

const APPROVAL_GROUP = -100888;
const APPROVAL_SUPERGROUP = -100999;
const APPROVAL_OWNER = 7001;
const APPROVAL_MEMBER = 7002;

function membershipUpdate(int $chatId, string $status, string $type = 'supergroup', int $from = APPROVAL_MEMBER): array
{
    return [
        'my_chat_member' => [
            'chat' => ['id' => $chatId, 'type' => $type, 'title' => 'Some <b>group</b>'],
            'from' => ['id' => $from, 'is_bot' => false, 'first_name' => 'Adder'],
            'date' => now()->timestamp,
            'old_chat_member' => ['status' => 'left', 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'Bot']],
            'new_chat_member' => ['status' => $status, 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'Bot']],
        ],
    ];
}

function migrationUpdate(string $field, int $chatId, int $otherId, string $type): array
{
    return [
        'message' => [
            'message_id' => random_int(1, 999999),
            'date' => now()->timestamp,
            'chat' => ['id' => $chatId, 'type' => $type, 'title' => 'Some group'],
            'from' => ['id' => APPROVAL_MEMBER, 'is_bot' => false, 'first_name' => 'Test'],
            $field => $otherId,
        ],
    ];
}

beforeEach(function () {
    config(['tbe-essence.groups.enabled' => true, 'tbe-essence.groups.require_approval' => true]);
    commandBus()->addCommand(ApprovalGroupCommand::class);
    ApprovalGroupCommand::$runs = 0;

    $this->bot = $this->makeBot();
    tenancy()->initialize($this->bot);
});

function approve(int $chatId = APPROVAL_GROUP, string $type = 'supergroup'): BotChat
{
    return BotChat::create(['chat_id' => $chatId, 'type' => $type, 'bot_status' => 'member', 'approved_at' => now()]);
}

it('records a group that adds the bot as pending, minting nobody', function () {
    Event::fake([BotChatJoined::class]);

    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'member'))->assertOk();

    $chat = BotChat::where('chat_id', APPROVAL_GROUP)->first();

    expect($chat->isApproved())->toBeFalse()
        ->and($chat->title)->toBe('Some <b>group</b>')
        ->and($chat->bot_status)->toBe('member')
        ->and(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->exists())->toBeFalse();
    Event::assertDispatched(BotChatJoined::class);
});

it('drops everything from an unapproved group, minting nobody', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(ApprovalGroupCommand::$runs)->toBe(0)
        ->and(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->exists())->toBeFalse();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && (int) $request['chat_id'] === APPROVAL_GROUP);
});

it('keeps dropping a group that joined and was never approved', function () {
    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'member'))->assertOk();

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(ApprovalGroupCommand::$runs)->toBe(0)
        ->and(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->exists())->toBeFalse();
});

it('drops a group again once its approval is taken back', function () {
    $chat = approve();
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();
    expect(ApprovalGroupCommand::$runs)->toBe(1);

    $chat->forceFill(['approved_at' => null])->save();
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(ApprovalGroupCommand::$runs)->toBe(1);
});

it('records a group first seen by its traffic, for the case the join was missed', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('hello', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(BotChat::where('chat_id', APPROVAL_GROUP)->first()?->isApproved())->toBeFalse();
});

it('runs handlers in an approved group', function () {
    approve();

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(ApprovalGroupCommand::$runs)->toBe(1);
});

it('drops button presses from an unapproved group', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate('x', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->exists())->toBeFalse();
});

it('lets any group in when approval is turned off', function () {
    config(['tbe-essence.groups.require_approval' => false]);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(ApprovalGroupCommand::$runs)->toBe(1);
});

it('records nothing while groups are off', function () {
    config(['tbe-essence.groups.enabled' => false]);

    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'member'));
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('hello', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    expect(BotChat::count())->toBe(0);
});

it('ignores channels', function () {
    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'administrator', 'channel'));

    expect(BotChat::count())->toBe(0);
});

it('takes back approval when the bot leaves, so coming back asks again', function () {
    Event::fake([BotChatLeft::class]);
    approve();

    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'kicked'))->assertOk();

    $chat = BotChat::where('chat_id', APPROVAL_GROUP)->first();

    expect($chat->isApproved())->toBeFalse()->and($chat->bot_status)->toBe('kicked');
    Event::assertDispatched(BotChatLeft::class);

    $this->postWebhookUpdate($this->bot, membershipUpdate(APPROVAL_GROUP, 'member'))->assertOk();

    expect($chat->fresh()->isApproved())->toBeFalse()->and($chat->fresh()->bot_status)->toBe('member');
});

it('keeps approval and open flows when a group becomes a supergroup', function (string $field, int $chatId, int $otherId, string $type) {
    approve(APPROVAL_GROUP, 'group');
    $user = $this->makeBotUser($this->bot, APPROVAL_MEMBER);
    $user->conversationIn(APPROVAL_GROUP)->changeState('flow');
    MessageOwner::record(APPROVAL_GROUP, 55, (int) $user->getKey());
    $meta = MessageMeta::create(['chat_id' => APPROVAL_GROUP, 'message_id' => 55]);

    $this->postWebhookUpdate($this->bot, migrationUpdate($field, $chatId, $otherId, $type))->assertOk();

    expect(BotChat::where('chat_id', APPROVAL_GROUP)->exists())->toBeFalse()
        ->and(BotChat::where('chat_id', APPROVAL_SUPERGROUP)->first()->isApproved())->toBeTrue()
        ->and(BotChat::where('chat_id', APPROVAL_SUPERGROUP)->first()->type)->toBe('supergroup')
        ->and($user->conversationIn(APPROVAL_SUPERGROUP)->state)->toBe('flow')
        ->and(MessageOwner::where('chat_id', APPROVAL_SUPERGROUP)->where('message_id', 55)->exists())->toBeTrue()
        ->and((int) $meta->fresh()->chat_id)->toBe(APPROVAL_SUPERGROUP);
})->with([
    'from the old chat' => ['migrate_to_chat_id', APPROVAL_GROUP, APPROVAL_SUPERGROUP, 'group'],
    'from the new chat' => ['migrate_from_chat_id', APPROVAL_SUPERGROUP, APPROVAL_GROUP, 'supergroup'],
]);

it('survives both migration messages and traffic that beat them', function () {
    approve(APPROVAL_GROUP, 'group');
    $user = $this->makeBotUser($this->bot, APPROVAL_MEMBER);
    $user->conversationIn(APPROVAL_GROUP)->changeState('old');

    // The new chat's first message arrives before the service message.
    BotChat::create(['chat_id' => APPROVAL_SUPERGROUP, 'type' => 'supergroup', 'bot_status' => 'member']);
    $user->conversationIn(APPROVAL_SUPERGROUP)->changeState('new');

    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', APPROVAL_GROUP, APPROVAL_SUPERGROUP, 'group'))->assertOk();
    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_from_chat_id', APPROVAL_SUPERGROUP, APPROVAL_GROUP, 'supergroup'))->assertOk();

    expect(BotChat::count())->toBe(1)
        ->and(BotChat::first()->isApproved())->toBeTrue()
        ->and(Conversation::where('chat_id', APPROVAL_GROUP)->exists())->toBeFalse()
        ->and($user->conversationIn(APPROVAL_SUPERGROUP)->state)->toBe('new');
});

it('leaves another bot in the same group alone on migration', function () {
    approve(APPROVAL_GROUP, 'group');
    tenancy()->end();
    $otherBot = $this->makeBot();
    tenancy()->initialize($otherBot);
    approve(APPROVAL_GROUP, 'group');
    tenancy()->end();

    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', APPROVAL_GROUP, APPROVAL_SUPERGROUP, 'group'))->assertOk();

    tenancy()->initialize($otherBot);
    expect(BotChat::where('chat_id', APPROVAL_GROUP)->exists())->toBeTrue();
});

it('does not migrate a group nobody approved into an approved one', function () {
    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', APPROVAL_GROUP, APPROVAL_SUPERGROUP, 'group'))->assertOk();

    expect(BotChat::where('chat_id', APPROVAL_SUPERGROUP)->first()?->isApproved() ?? false)->toBeFalse();
});

it('counts a user met in a group as having no private chat, until they use one', function () {
    approve();

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/approvalgroup', APPROVAL_MEMBER), APPROVAL_GROUP))->assertOk();

    $user = BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->first();

    expect($user->has_private_chat)->toBeFalse()
        ->and($user->isReachable())->toBeFalse()
        ->and(BotUser::reachable()->whereKey($user->getKey())->exists())->toBeFalse();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', APPROVAL_MEMBER))->assertOk();

    expect($user->fresh()->has_private_chat)->toBeTrue()
        ->and($user->fresh()->isReachable())->toBeTrue();
});

it('does not take an inline query as proof of a private chat', function () {
    $this->postWebhookUpdate($this->bot, ['inline_query' => [
        'id' => '1',
        'from' => ['id' => APPROVAL_MEMBER, 'is_bot' => false, 'first_name' => 'Test'],
        'query' => 'x',
        'offset' => '',
    ]])->assertOk();

    expect(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->first()?->has_private_chat)->toBeFalse();
});

it('leaves existing and privately met users reachable', function () {
    $existing = $this->makeBotUser($this->bot, APPROVAL_OWNER);
    expect($existing->fresh()->has_private_chat)->toBeTrue();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', APPROVAL_MEMBER))->assertOk();

    expect(BotUser::where('telegram_user_peer_id', APPROVAL_MEMBER)->first()->has_private_chat)->toBeTrue();
});

it('lets someone with the ability approve and revoke a group', function () {
    Gate::define('groups.manage', fn (BotUser $user) => (int) $user->telegram_user_peer_id === APPROVAL_OWNER);
    $this->makeBotUser($this->bot, APPROVAL_OWNER);
    $chat = BotChat::create(['chat_id' => APPROVAL_GROUP, 'type' => 'supergroup', 'bot_status' => 'member']);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/groups', APPROVAL_OWNER))->assertOk();
    $this->assertTelegramSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
        && str_contains((string) ($request['reply_markup'] ?? ''), 'GROUPCHATS'));

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('GROUPCHATS', 'approve', [$chat->id]), APPROVAL_OWNER))->assertOk();
    expect($chat->fresh()->isApproved())->toBeTrue();

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('GROUPCHATS', 'revoke', [$chat->id]), APPROVAL_OWNER))->assertOk();
    expect($chat->fresh()->isApproved())->toBeFalse();
});

it('refuses approval to anyone without the ability', function () {
    Gate::define('groups.manage', fn () => false);
    $this->makeBotUser($this->bot, APPROVAL_MEMBER);
    $chat = BotChat::create(['chat_id' => APPROVAL_GROUP, 'type' => 'supergroup', 'bot_status' => 'member']);

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('GROUPCHATS', 'approve', [$chat->id]), APPROVAL_MEMBER));

    expect($chat->fresh()->isApproved())->toBeFalse();
});

it('cannot approve a group from inside a group', function () {
    Gate::define('groups.manage', fn () => true);
    approve(-100111);
    $chat = BotChat::create(['chat_id' => APPROVAL_GROUP, 'type' => 'supergroup', 'bot_status' => 'member']);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate(encodeCallback('GROUPCHATS', 'approve', [$chat->id]), APPROVAL_MEMBER), -100111));

    expect($chat->fresh()->isApproved())->toBeFalse();
});
