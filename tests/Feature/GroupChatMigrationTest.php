<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\MessageOwner;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

/*
 * A group becoming a supergroup gets a new chat id, and open flows follow it.
 * A user met in a group or by an inline query has no private chat, so the bot
 * cannot message them until they use one.
 */

uses(RefreshDatabase::class);

class MigrationGroupCommand extends Command
{
    protected string $name = 'migrationgroup';

    protected ChatScope $chatScope = ChatScope::Groups;

    public static int $runs = 0;

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

const MIGRATION_GROUP = -100888;
const MIGRATION_SUPERGROUP = -100999;
const MIGRATION_MEMBER = 7002;

function migrationUpdate(string $field, int $chatId, int $otherId, string $type): array
{
    return [
        'message' => [
            'message_id' => random_int(1, 999999),
            'date' => now()->timestamp,
            'chat' => ['id' => $chatId, 'type' => $type, 'title' => 'Some group'],
            'from' => ['id' => MIGRATION_MEMBER, 'is_bot' => false, 'first_name' => 'Test'],
            $field => $otherId,
        ],
    ];
}

beforeEach(function () {
    config(['tbe-essence.groups.enabled' => true]);
    commandBus()->addCommand(MigrationGroupCommand::class);
    MigrationGroupCommand::$runs = 0;

    $this->bot = $this->makeBot();
    tenancy()->initialize($this->bot);
});

it('runs in any group it is added to', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/migrationgroup', MIGRATION_MEMBER), MIGRATION_GROUP))->assertOk();

    expect(MigrationGroupCommand::$runs)->toBe(1);
});

it('keeps open flows and button owners when a group becomes a supergroup', function (string $field, int $chatId, int $otherId, string $type) {
    $user = $this->makeBotUser($this->bot, MIGRATION_MEMBER);
    $user->conversationIn(MIGRATION_GROUP)->changeState('flow');
    MessageOwner::record(MIGRATION_GROUP, 55, (int) $user->getKey());
    $meta = MessageMeta::create(['chat_id' => MIGRATION_GROUP, 'message_id' => 55]);

    $this->postWebhookUpdate($this->bot, migrationUpdate($field, $chatId, $otherId, $type))->assertOk();

    expect($user->conversationIn(MIGRATION_SUPERGROUP)->state)->toBe('flow')
        ->and(Conversation::where('chat_id', MIGRATION_GROUP)->exists())->toBeFalse()
        ->and(MessageOwner::where('chat_id', MIGRATION_SUPERGROUP)->where('message_id', 55)->exists())->toBeTrue()
        ->and((int) $meta->fresh()->chat_id)->toBe(MIGRATION_SUPERGROUP);
})->with([
    'from the old chat' => ['migrate_to_chat_id', MIGRATION_GROUP, MIGRATION_SUPERGROUP, 'group'],
    'from the new chat' => ['migrate_from_chat_id', MIGRATION_SUPERGROUP, MIGRATION_GROUP, 'supergroup'],
]);

it('survives both migration messages and traffic that beat them', function () {
    $user = $this->makeBotUser($this->bot, MIGRATION_MEMBER);
    $user->conversationIn(MIGRATION_GROUP)->changeState('old');
    $user->conversationIn(MIGRATION_SUPERGROUP)->changeState('new');

    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', MIGRATION_GROUP, MIGRATION_SUPERGROUP, 'group'))->assertOk();
    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_from_chat_id', MIGRATION_SUPERGROUP, MIGRATION_GROUP, 'supergroup'))->assertOk();

    expect(Conversation::where('chat_id', MIGRATION_GROUP)->exists())->toBeFalse()
        ->and($user->conversationIn(MIGRATION_SUPERGROUP)->state)->toBe('new');
});

it('leaves another bot in the same group alone', function () {
    tenancy()->end();
    $otherBot = $this->makeBot();
    tenancy()->initialize($otherBot);
    $other = $this->makeBotUser($otherBot, 7003);
    $other->conversationIn(MIGRATION_GROUP)->changeState('theirs');
    tenancy()->end();

    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', MIGRATION_GROUP, MIGRATION_SUPERGROUP, 'group'))->assertOk();

    tenancy()->initialize($otherBot);
    expect(Conversation::where('chat_id', MIGRATION_GROUP)->where('bot_user_id', $other->getKey())->exists())->toBeTrue();
});

it('does nothing with migration messages while groups are off', function () {
    config(['tbe-essence.groups.enabled' => false]);
    $user = $this->makeBotUser($this->bot, MIGRATION_MEMBER);
    $user->conversationIn(MIGRATION_GROUP)->changeState('flow');

    $this->postWebhookUpdate($this->bot, migrationUpdate('migrate_to_chat_id', MIGRATION_GROUP, MIGRATION_SUPERGROUP, 'group'))->assertOk();

    expect($user->conversationIn(MIGRATION_GROUP)->state)->toBe('flow');
});

it('counts a user met in a group as having no private chat, until they use one', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/migrationgroup', MIGRATION_MEMBER), MIGRATION_GROUP))->assertOk();

    $user = BotUser::where('telegram_user_peer_id', MIGRATION_MEMBER)->first();

    expect($user->has_private_chat)->toBeFalse()
        ->and($user->isReachable())->toBeFalse()
        ->and(BotUser::reachable()->whereKey($user->getKey())->exists())->toBeFalse();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', MIGRATION_MEMBER))->assertOk();

    expect($user->fresh()->has_private_chat)->toBeTrue()
        ->and($user->fresh()->isReachable())->toBeTrue();
});

it('does not take an inline query as proof of a private chat', function () {
    $this->postWebhookUpdate($this->bot, ['inline_query' => [
        'id' => '1',
        'from' => ['id' => MIGRATION_MEMBER, 'is_bot' => false, 'first_name' => 'Test'],
        'query' => 'x',
        'offset' => '',
    ]])->assertOk();

    expect(BotUser::where('telegram_user_peer_id', MIGRATION_MEMBER)->first()?->has_private_chat)->toBeFalse();
});

it('leaves existing and privately met users reachable', function () {
    $existing = $this->makeBotUser($this->bot, 7001);
    expect($existing->fresh()->has_private_chat)->toBeTrue();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', MIGRATION_MEMBER))->assertOk();

    expect(BotUser::where('telegram_user_peer_id', MIGRATION_MEMBER)->first()->has_private_chat)->toBeTrue();
});

it('counts someone first met in a group as new when they open the private chat', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/migrationgroup', MIGRATION_MEMBER), MIGRATION_GROUP))->assertOk();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', MIGRATION_MEMBER))->assertOk();
    expect(wHook()->user()->isNewToPrivateChat())->toBeTrue();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', MIGRATION_MEMBER))->assertOk();
    expect(wHook()->user()->isNewToPrivateChat())->toBeFalse();
});

it('counts a brand new private user as new and a returning one as not', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', MIGRATION_MEMBER))->assertOk();
    expect(wHook()->user()->isNewToPrivateChat())->toBeTrue();

    $this->makeBotUser($this->bot, 7005);
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/help', 7005))->assertOk();
    expect(wHook()->user()->isNewToPrivateChat())->toBeFalse();
});

it('does not count a user as new in a group', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/migrationgroup', MIGRATION_MEMBER), MIGRATION_GROUP))->assertOk();

    expect(wHook()->user()->isNewToPrivateChat())->toBeFalse();
});
