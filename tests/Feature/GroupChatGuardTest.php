<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Models\BotUser;

/*
 * Groups are opt-in (tbe-essence.groups.enabled, off by default). Until a
 * bot turns them on, messages and button presses from a group are
 * acknowledged and dropped before anything runs: no BotUser is created, no
 * state is touched and nothing is sent to the sender's DM.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = $this->makeBot();
});

it('drops a group message without minting a user or replying', function (string $type) {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/start', peerId: 4001), type: $type))->assertOk();

    expect(BotUser::query()->where('telegram_user_peer_id', 4001)->exists())->toBeFalse();
    Http::assertNothingSent();
})->with(['group', 'supergroup', 'channel']);

it('leaves the sender\'s private-chat state alone', function () {
    $user = $this->makeBotUser($this->bot, 4002, ['state' => 'SOMEFLOW|step']);

    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeMessageUpdate('/start', peerId: 4002)))->assertOk();

    expect($user->privateConversation()->state)->toBe('SOMEFLOW|step');
});

it('drops a button press on a group message', function () {
    $this->postWebhookUpdate($this->bot, $this->inGroupChat($this->makeCallbackQueryUpdate('X|y', peerId: 4003)))->assertOk();

    expect(BotUser::query()->where('telegram_user_peer_id', 4003)->exists())->toBeFalse();
    Http::assertNothingSent();
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
