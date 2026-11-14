<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Exceptions\TelegramSDKException;

/*
 * A request that never left can be sent again. One that may have arrived
 * cannot: Telegram has no idempotency key, so a retry could message twice.
 */

function transportSees(array $responses): void
{
    $factory = new Factory;
    $factory->fake(['*' => Http::sequence($responses)]);
    Http::swap($factory);
}

function okMessage(): array
{
    return ['ok' => true, 'result' => ['message_id' => 1, 'date' => 1, 'chat' => ['id' => 1, 'type' => 'private']]];
}

function send(): mixed
{
    return telegramApi('123:abc')->sendMessage(['chat_id' => 1, 'text' => 'hi']);
}

function telegramCalls(): int
{
    return Http::recorded(fn (Request $request) => str_contains($request->url(), 'sendMessage'))->count();
}

it('sends again when the connection never opened', function () {
    $calls = 0;
    $factory = new Factory;
    $factory->fake(function () use (&$calls) {
        return ++$calls === 1
            ? Factory::failedConnection('cURL error 7: Failed to connect to api.telegram.org')(new class
            {
                public function toPsrRequest()
                {
                    return new GuzzleHttp\Psr7\Request('POST', 'https://api.telegram.org');
                }
            })
            : Http::response(okMessage());
    });
    Http::swap($factory);

    send();

    expect($calls)->toBe(2);
});

it('does not send again after a read timeout, since the message may have arrived', function () {
    $calls = 0;
    $factory = new Factory;
    $factory->fake(function () use (&$calls) {
        $calls++;

        return Factory::failedConnection('cURL error 28: Operation timed out after 15001 milliseconds')(new class
        {
            public function toPsrRequest()
            {
                return new GuzzleHttp\Psr7\Request('POST', 'https://api.telegram.org');
            }
        });
    });
    Http::swap($factory);

    expect(fn () => send())->toThrow(TelegramSDKException::class);
    expect($calls)->toBe(1);
});

it('waits out a short rate limit', function () {
    transportSees([
        Http::response(['ok' => false, 'error_code' => 429, 'description' => 'Too Many Requests', 'parameters' => ['retry_after' => 1]], 429),
        Http::response(okMessage()),
    ]);

    send();

    expect(telegramCalls())->toBe(2);
});

it('does not hold a request through a long rate limit', function () {
    transportSees([
        Http::response(['ok' => false, 'error_code' => 429, 'description' => 'Too Many Requests', 'parameters' => ['retry_after' => 30]], 429),
    ]);

    expect(fn () => send())->toThrow(TelegramSDKException::class);
    expect(telegramCalls())->toBe(1);
});

it('does not send again on an answer telegram meant', function () {
    transportSees([
        Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403),
    ]);

    expect(fn () => send())->toThrow(TelegramSDKException::class);
    expect(telegramCalls())->toBe(1);
});
