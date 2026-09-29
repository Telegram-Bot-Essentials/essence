<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Support\BotCommandMenu;

uses(RefreshDatabase::class);

it('publishes the configured commands to the bot menu', function () {
    $bot = $this->makeBot();

    app(BotCommandMenu::class)->register($bot);

    $this->assertTelegramSent(
        fn ($request) => $request->url() === 'https://api.telegram.org/bot'.$bot->bot_token.'/setMyCommands'
            && count(json_decode((string) $request['commands'], true)) === count(config('tbe-essence.commands'))
    );
});

it('carries on with the other bots when one fails during set-webhook --all', function () {
    $good = $this->makeBot(['bot_token' => '111:good']);
    $bad = $this->makeBot(['bot_token' => '222:bad']);

    // Replace the catch-all stub TestCase::setUp() registered; first match wins.
    Http::swap(new Factory);
    Http::fake([
        '*'.$bad->bot_token.'/*' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401),
        '*' => Http::response(['ok' => true, 'result' => true]),
    ]);

    config(['app.url' => 'https://shop.test']);

    $this->artisan('tbe:set-webhook', ['--all' => true])->assertFailed();

    Http::assertSent(fn ($request) => str_contains($request->url(), $good->bot_token.'/setMyCommands'));
});
