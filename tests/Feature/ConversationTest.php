<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Support\WebhookContext;
use TelegramBotEssentials\Essence\Tests\Fixtures\StepwiseStateAnswer;
use TelegramBotEssentials\Essence\Tests\Fixtures\SupportReplyKey;

/*
 * State and the reply menu belong to a conversation - one user in one chat
 * and forum topic - so a flow in one chat never sees another's.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->user = $this->makeBotUser($this->bot, 9001);
});

it('keeps one conversation per chat and topic', function () {
    $private = $this->user->privateConversation();

    expect($private->chat_id)->toBe(9001)
        ->and($private->thread_id)->toBe(0)
        ->and($private->isPrivate())->toBeTrue()
        ->and($this->user->privateConversation()->id)->toBe($private->id)
        ->and($this->user->conversationIn(-100555)->id)->not->toBe($private->id)
        ->and($this->user->conversationIn(-100555, 7)->id)->not->toBe($this->user->conversationIn(-100555)->id)
        ->and($this->user->conversationIn(-100555)->isPrivate())->toBeFalse();
});

it('runs a private message against the private conversation only', function () {
    stateAnswerBus()->addStateAnswer(StepwiseStateAnswer::class);
    StepwiseStateAnswer::$handled = [];
    $this->user->conversationIn(-100555)->changeState(encodeAnswerState('STEPWISE', 'answer', ['step' => 'text']));

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hello', peerId: 9001))->assertOk();

    expect(StepwiseStateAnswer::$handled)->toBe([])
        ->and($this->user->conversationIn(-100555)->state)->not->toBeNull();
});

it('binds the update\'s conversation on the webhook', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hello', peerId: 9001))->assertOk();

    expect(wHook()->conversation()->is($this->user->privateConversation()))->toBeTrue()
        ->and(wHook()->chatId())->toBe(9001)
        ->and(wHook()->threadId())->toBeNull();
});

it('acts on another user through their private conversation', function () {
    $other = $this->makeBotUser($this->bot, 9002);
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hello', peerId: 9001))->assertOk();
    $own = wHook()->conversation();

    wHook()->runForUser($other, function () use ($other) {
        expect(wHook()->conversation()->is($other->privateConversation()))->toBeTrue();
    });

    expect(wHook()->conversation())->toBe($own);
});

it('restores a queued context into its conversation, or the private chat for an old payload', function () {
    $group = $this->user->conversationIn(-100555, 3);

    (new WebhookContext(botId: $this->bot->id, botUserId: $this->user->id, botToken: 'x', chatId: -100555, threadId: 3))->apply();
    expect(wHook()->conversation()->is($group))->toBeTrue();

    wHook()->importContext(['bot_id' => $this->bot->id, 'bot_user_id' => $this->user->id, 'bot_token' => 'x', 'update' => []]);
    expect(wHook()->conversation()->is($this->user->privateConversation()))->toBeTrue();
});

it('builds a user\'s keyboard from their private conversation\'s menu', function () {
    config(['tbe-essence.keyboard.menus.other' => [[SupportReplyKey::class]]]);
    $this->user->privateConversation()->changeMenu('other');

    expect(replyKeyboard($this->user)->toArray()['keyboard'])->toBe([['Support']]);
});

it('moves state and menu off bot_users and back', function () {
    $this->user->privateConversation()->update(['state' => 'X', 'menu' => 'admin']);
    $migration = require __DIR__.'/../../database/migrations/2026_10_13_100000_move_state_and_menu_to_conversations.php';

    $migration->down();
    expect(DB::table('bot_users')->where('id', $this->user->id)->first(['state', 'menu']))->toEqual((object) ['state' => 'X', 'menu' => 'admin'])
        ->and(Schema::hasTable('conversations'))->toBeFalse();

    $migration->up();
    expect(Schema::hasColumns('bot_users', ['state']))->toBeFalse()
        ->and(Conversation::query()->where('bot_user_id', $this->user->id)->sole()->only(['chat_id', 'thread_id', 'state', 'menu']))
        ->toBe(['chat_id' => 9001, 'thread_id' => 0, 'state' => 'X', 'menu' => 'admin']);
});
