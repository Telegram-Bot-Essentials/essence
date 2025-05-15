<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Support\Webhook;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

beforeEach(function () {
    Webhook::setApi(null);
    Webhook::setUpdate(null);
    Webhook::setBot(null);
    Webhook::setUser(null);
});

test('Set bot in wHook works', function () {
    $unique_id = rand(0, 1000);
    $bot = Bot::firstOrCreate([
        'unique_id' => $unique_id,
        'bot_token' => 'xxx',
        'secret_token' => 'xxx',
        'bot_owner_peer_id' => 11111,
    ]);
    Webhook::setBot($bot);
    $setBot = Webhook::bot();
    expect($setBot->unique_id)->toBe($bot->unique_id);
});

test('Set update in wHook works', function () {
    $payload = [
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'from' => [
                'id' => 992258179,
                'is_bot' => false,
                'first_name' => 'Test User'
            ],
            'chat' => ['id' => 992258179],
            'text' => '/start'
        ]
    ];
    $update = new Update($payload);
    Webhook::setUpdate($update);
    $setUpdate = Webhook::update();
    expect($setUpdate)->toBe($update);
});

test('Set bot user in wHook works', function () {
    $botUser = BotUser::factory()->create();
    Webhook::setUser($botUser);
    $setBotUser = Webhook::user();
    expect($setBotUser->id)->toBe($botUser->id);
});

test('Set api in wHook works', function () {
    $bot = Bot::factory()->create();
    $api = new Api($bot->bot_token);
    Webhook::setApi($api);
    $setApi = Webhook::api();
    expect($setApi)->toBe($api);
});

test('Check function in wHook works when empty', function () {
    expect(Webhook::check())->toBeFalse();
});

test('Check function in wHook works when all set', function () {
    $bot = Bot::factory()->create();
    $api = new Api($bot->bot_token);
    $botUser = BotUser::factory()->create();
    $update = new Update(['update_id' => 1]);

    Webhook::setApi($api);
    Webhook::setUpdate($update);
    Webhook::setBot($bot);
    Webhook::setUser($botUser);

    expect(Webhook::check())->toBeTrue();
});