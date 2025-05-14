<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Telegram\Bot\Objects\Update;

test('Set bot in wHook works', function () {
    $unique_id = rand(0, 1000);
    $bot = Bot::firstOrCreate([
        'unique_id' => $unique_id,
        'bot_token' => 'xxx',
        'secret_token' => 'xxx',
        'bot_owner_peer_id' => 11111,
    ]);
    wHook()->setBot($bot);
    $setBot = wHook()->bot();
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
    wHook()->setUpdate($update);
    $setUpdate = wHook()->update();
    expect($setUpdate)->toBe($update);
});

//test('Set bot user in wHook works', function () {
//    $botUser = BotUser::factory()->create();
//
//    wHook()->setUser($botUser);
//    $setBotUser = wHook()->user();
//    expect($setBotUser)->toBe($botUser);
//});