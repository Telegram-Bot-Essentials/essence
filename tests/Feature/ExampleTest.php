<?php

use Elyar\TelegramBotEssentials\Models\Bot;

it('returns a successful response', function () {
    $response = $this->get('/api/telegram/bot/' . rand(0, 1000) . '/webhook');

    $response->assertStatus(200);
});

it('Returns 403 for unauthorized requests', function () {
    $unique_id = rand(0, 1000);
    $bot = Bot::firstOrCreate([
        'unique_id' => $unique_id,
        'bot_token' => 'xxx',
        'secret_token' => 'xxx',
        'bot_owner_peer_id' => 11111,
    ]);

//    expect(Bot::count())->toBe(1);
    $response = $this->post('/api/telegram/bot/' . $unique_id . '/webhook');

    $response->assertStatus(403);
});

it('Returns 404 if bot doesnt exist', function () {
    $response = $this->post('/api/telegram/bot/' . rand(0, 1000) . '/webhook');

    $response->assertStatus(404);
});

test('confirm environment is set to testing', function () {
    expect(config('app.env'))->toBe('testing');
});
