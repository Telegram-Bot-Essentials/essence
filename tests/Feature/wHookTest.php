<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    wHook()->setApi(null);
    wHook()->setUpdate(null);
    wHook()->setBot(null);
    wHook()->setUser(null);
});

test('api() throws HttpException when api is null', function () {
    wHook()->setApi(null);
    expect(fn() => wHook()->api())
        ->toThrow(HttpException::class, 'Failed to retrieve API service.');
});

test('update() throws HttpException when update is null', function () {
    wHook()->setUpdate(null);
    expect(fn() => wHook()->update())
        ->toThrow(HttpException::class, 'Failed to retrieve Updates.');
});

test('bot() throws HttpException when bot is null', function () {
    wHook()->setBot(null);
    expect(fn() => wHook()->bot())
        ->toThrow(HttpException::class, 'Failed to retrieve bot.');
});

test('user() throws HttpException when user is null', function () {
    wHook()->setUser(null);
    expect(fn() => wHook()->user())
        ->toThrow(HttpException::class, 'Failed to retrieve telegram user.');
});

test('check() returns false when any property is null', function () {
    $bot = Bot::factory()->create();
    $api = new Api($bot->bot_token);
    $botUser = BotUser::factory()->create();
    $update = new Update(['update_id' => 1]);

    wHook()->setApi(null);
    wHook()->setUpdate($update);
    wHook()->setBot($bot);
    wHook()->setUser($botUser);
    expect(wHook()->check())->toBeFalse();

    wHook()->setApi($api);
    wHook()->setUpdate(null);
    expect(wHook()->check())->toBeFalse();

    wHook()->setUpdate($update);
    wHook()->setBot(null);
    expect(wHook()->check())->toBeFalse();

    wHook()->setBot($bot);
    wHook()->setUser(null);
    expect(wHook()->check())->toBeFalse();
});

test('check() returns true when all properties are set', function () {
    $bot = Bot::factory()->create();
    $api = new Api($bot->bot_token);
    $botUser = BotUser::factory()->create();
    $update = new Update(['update_id' => 1]);

    wHook()->setApi($api);
    wHook()->setUpdate($update);
    wHook()->setBot($bot);
    wHook()->setUser($botUser);

    expect(wHook()->check())->toBeTrue();
});