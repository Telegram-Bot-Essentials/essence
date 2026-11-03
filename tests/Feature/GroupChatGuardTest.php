<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Models\BotUser;

/*
 * Groups need no switching on, but nothing runs there unless a handler opts in
 * (ChatScope). Channels never send the bot anything but button presses, and
 * nothing done in a group touches the sender's private chat.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = $this->makeBot();
});

it('drops a message in a channel without minting a user or replying', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/help', peerId: 4001), type: 'channel'))->assertOk();

    expect(BotUser::query()->where('telegram_user_peer_id', 4001)->exists())->toBeFalse();
    Http::assertNothingSent();
});

it('lets a button press on a channel message reach the handlers', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate('X|y', peerId: 4007), type: 'channel'))->assertOk();

    expect(BotUser::query()->where('telegram_user_peer_id', 4007)->exists())->toBeTrue();
});

it('leaves the sender\'s private-chat state alone', function () {
    $user = $this->makeBotUser($this->bot, 4002, ['state' => 'SOMEFLOW|step']);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/start', peerId: 4002)))->assertOk();

    expect($user->privateConversation()->state)->toBe('SOMEFLOW|step');
});

it('still serves private chats, inline queries and inline-mode buttons', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 4004))->assertOk();

    $this->postWebhookUpdate($this->bot, [
        'inline_query' => [
            'id' => '1',
            'from' => ['id' => 4005, 'is_bot' => false, 'first_name' => 'Test'],
            'query' => '',
            'offset' => '',
        ],
    ])->assertOk();

    $this->postWebhookUpdate($this->bot, [
        'callback_query' => [
            'id' => '2',
            'from' => ['id' => 4006, 'is_bot' => false, 'first_name' => 'Test'],
            'inline_message_id' => 'abc',
            'chat_instance' => '1',
            'data' => 'X|y',
        ],
    ])->assertOk();

    expect(BotUser::query()->whereIn('telegram_user_peer_id', [4004, 4005, 4006])->count())->toBe(3);
});
