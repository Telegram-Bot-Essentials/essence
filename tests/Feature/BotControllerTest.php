<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

$headers = [
    'Authorization' => 'xxx',
];

it('returns 403 to requests without authorization', function () use ($headers) {
    $response = getJson('/api/bots');
    $response->assertStatus(403);
});

it('can list bots', function () use ($headers) {
    Bot::factory()->count(3)->create();

    $response = getJson('/api/bots', $headers);

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can show a bot', function () use ($headers) {
    $bot = Bot::factory()->create();

    $response = getJson("/api/bots/{$bot->id}", $headers);

    $response->assertOk()
        ->assertJsonPath('data.id', $bot->id);
});

it('can create a bot', function () use ($headers) {
    $data = [
        'bot_token' => 'xxx',
        'bot_owner_peer_id' => '992258179',
    ];

    $response = postJson('/api/bots', $data, $headers);

    $response->assertCreated()
        ->assertJsonPath('data.bot_token', 'xxx');

    expect(Bot::where('bot_token', 'xxx')->exists())->toBeTrue();
});

it('can update a bot', function () use ($headers) {
    $bot = Bot::factory()->create([
        'bot_token' => 'xxx'
    ]);

    $data = [
        'bot_token' => 'yyy'
    ];

    $response = putJson("/api/bots/{$bot->id}", $data, $headers);

    $response->assertOk()
        ->assertJsonPath('data.bot_token', 'yyy');

    expect($bot->fresh()->bot_token)->toBe('yyy');
});

it('can delete a bot', function () use ($headers) {
    $bot = Bot::factory()->create();

    $response = deleteJson("/api/bots/{$bot->id}", headers: $headers);

    $response->assertNoContent();

    expect(Bot::find($bot->id))->toBeNull();
});
