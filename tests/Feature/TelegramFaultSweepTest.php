<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/*
 * sweepTelegramFailures() runs a flow once to count its Telegram calls, then
 * again with each call failing in turn, checking the same promise every time.
 */

function sendTwo(): void
{
    $api = telegramApi('123:abc');
    $api->sendMessage(['chat_id' => 1, 'text' => 'first']);
    $api->sendMessage(['chat_id' => 1, 'text' => 'second']);
}

it('fails each call in turn', function () {
    $outcomes = [];

    $calls = $this->sweepTelegramFailures(function () use (&$outcomes) {
        try {
            sendTwo();
            $outcomes[] = 'finished';
        } catch (Throwable $e) {
            $outcomes[] = 'failed';
        }
    }, fn () => null);

    expect($calls)->toBe(2)
        // The counting run, then each of the two calls failing.
        ->and($outcomes)->toBe(['finished', 'failed', 'failed']);
});

it('tells the check which call failed', function () {
    $seen = [];

    $this->sweepTelegramFailures(fn () => rescue(fn () => sendTwo(), report: false), function (int $failed) use (&$seen) {
        $seen[] = $failed;
    });

    expect($seen)->toBe([1, 2]);
});

it('sees what runs after a commit, however a flow gets there', function () {
    config(['queue.default' => 'sync']);
    $sent = [];

    $calls = $this->sweepTelegramFailures(function () use (&$sent) {
        DB::transaction(function () {
            background('after commit', static fn () => telegramApi('123:abc')->sendMessage(['chat_id' => 1, 'text' => 'hi']));
        });
    }, function () use (&$sent) {
        $sent[] = true;
    });

    expect($calls)->toBe(1)->and($sent)->toBe([true]);
});

it('undoes the database between runs', function () {
    $counts = [];
    $bot = $this->makeBot();
    $this->makeBotUser($bot, 7);

    $this->sweepTelegramFailures(function () {
        DB::table('telegram_users')->update(['first_name' => 'changed']);
        rescue(fn () => sendTwo(), report: false);
    }, function () use (&$counts) {
        $counts[] = DB::table('telegram_users')->where('first_name', 'changed')->count();
    });

    expect($counts)->toHaveCount(2)
        ->and($counts[0])->toBeGreaterThan(0);
    expect(DB::table('telegram_users')->where('first_name', 'changed')->count())->toBe(0);
});
