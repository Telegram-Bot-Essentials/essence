<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use function Pest\Laravel\postJson;

it('returns a successful response for any get request', function () {
    $bot = Bot::factory()->create();
    $response = $this->get('/api/' . $bot->unique_id . '/telegram/bot/webhook');

    $response->assertStatus(200);
});

it('Handles valid Telegram webhook requests', function () {
    $bot = Bot::factory()->create([
        'secret_token' => 'test-secret',
        'bot_token' => 'test-token',
        'bot_owner_peer_id' => 11111,
    ]);

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

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        $payload,
        ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']
    );

    $response->assertOk();
});

it('Returns 403 for unauthorized requests', function () {
    $unique_id = rand(0, 1000);
    Bot::firstOrCreate([
        'unique_id' => $unique_id,
        'bot_token' => '7519123072:AAF8UQwhks8znGNUTDbDa_gj4VGiCSvhBK8',
        'secret_token' => 'xxx',
        'bot_owner_peer_id' => 11111,
    ]);

    $response = $this->post('/api/' . $unique_id . '/telegram/bot/webhook');

    $response->assertStatus(403);
});

it('Returns 404 if bot doesnt exist', function () {
    $response = $this->post('/api/' . rand(0, 1000) . '/telegram/bot/webhook');

    $response->assertStatus(404);
});