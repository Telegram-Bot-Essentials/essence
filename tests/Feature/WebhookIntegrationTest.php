<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/*
 * Proves the shared Testing\TestCase kit works end-to-end: a real inbound
 * webhook request, through the real route/middleware/controller stack,
 * resulting in a real (faked) outbound Telegram API call. This is also the
 * reference example for companion packages extending the same base.
 */

it('replies with the main menu text when its reply key button is pressed', function () {
    $bot = $this->makeBot();

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('Main Menu 🔰'))
        ->assertOk();

    $this->assertTelegramSent(
        fn ($request) => $request->url() === 'https://api.telegram.org/bot'.$bot->bot_token.'/sendMessage'
            && str_contains((string) $request['text'], 'Main Menu loaded')
    );
});

it('rejects a webhook request with the wrong secret token', function () {
    $bot = $this->makeBot();

    $response = $this->postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        $this->makeMessageUpdate('anything'),
        ['x-telegram-bot-api-secret-token' => 'not-the-real-secret']
    );

    $response->assertStatus(204);
    Http::assertNothingSent();
});
