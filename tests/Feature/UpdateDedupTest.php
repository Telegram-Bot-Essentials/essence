<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use TelegramBotEssentials\Essence\Http\Middleware\TelegramBotAuthentication;
use TelegramBotEssentials\Essence\Models\Bot;

uses(RefreshDatabase::class);

function makeUpdateRequest(Bot $bot, int $updateId, int $peerId = 555): Request
{
    return Request::create(
        uri: '/'.$bot->unique_id.'/telegram/bot/webhook',
        method: 'POST',
        content: json_encode([
            'update_id' => $updateId,
            'message' => [
                'message_id' => 1,
                'date' => now()->timestamp,
                'chat' => ['id' => $peerId, 'type' => 'private'],
                'from' => ['id' => $peerId, 'is_bot' => false, 'first_name' => 'Test'],
                'text' => 'hello',
            ],
        ]),
        server: ['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => (string) $bot->secret_token],
    );
}

it('processes an update the first time it is seen', function () {
    $bot = Bot::factory()->create();
    tenancy()->initialize($bot);

    $calls = 0;
    $middleware = app(TelegramBotAuthentication::class);

    $response = $middleware->handle(makeUpdateRequest($bot, updateId: 1001), function () use (&$calls) {
        $calls++;

        return response('OK');
    });

    expect($calls)->toBe(1)
        ->and($response->getContent())->toBe('OK');
});

it('drops a redelivered update instead of reprocessing it', function () {
    $bot = Bot::factory()->create();
    tenancy()->initialize($bot);

    $calls = 0;
    $middleware = app(TelegramBotAuthentication::class);
    $next = function () use (&$calls) {
        $calls++;

        return response('OK');
    };

    $middleware->handle(makeUpdateRequest($bot, updateId: 2002), $next);
    wHook()->clear();
    $middleware->handle(makeUpdateRequest($bot, updateId: 2002), $next);

    expect($calls)->toBe(1);
});

it('releases the dedup claim when processing throws, so a retry is not dropped', function () {
    $bot = Bot::factory()->create();
    tenancy()->initialize($bot);

    $calls = 0;
    $middleware = app(TelegramBotAuthentication::class);
    $failThenSucceed = function () use (&$calls) {
        $calls++;
        if ($calls === 1) {
            throw new RuntimeException('handler blew up');
        }

        return response('OK');
    };

    expect(fn () => $middleware->handle(makeUpdateRequest($bot, updateId: 3003), $failThenSucceed))
        ->toThrow(RuntimeException::class);

    wHook()->clear();
    $middleware->handle(makeUpdateRequest($bot, updateId: 3003), $failThenSucceed);

    expect($calls)->toBe(2);
});
