<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list bots', function () {
    Bot::factory()->count(3)->create();

    $response = $this->getJson('/api/bots');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can show a bot', function () {
    $bot = Bot::factory()->create();

    $response = $this->getJson("/api/bots/{$bot->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $bot->id);
});

it('can create a bot', function () {
    $data = [
        'bot_token' => 'xxx',
        'bot_owner_peer_id' => '992258179',
    ];

    $response = $this->postJson('/api/bots', $data);

    $response->assertCreated()
        ->assertJsonPath('data.bot_token', 'xxx');

    expect(Bot::where('bot_token', 'xxx')->exists())->toBeTrue();
});

it('can update a bot', function () {
    $bot = Bot::factory()->create([
        'bot_token' => 'xxx'
    ]);

    $data = [
        'bot_token' => 'yyy'
    ];

    $response = $this->putJson("/api/bots/{$bot->id}", $data);

    $response->assertOk()
        ->assertJsonPath('data.bot_token', 'yyy');

    expect($bot->fresh()->bot_token)->toBe('yyy');
});

it('can delete a bot', function () {
    $bot = Bot::factory()->create();

    $response = $this->deleteJson("/api/bots/{$bot->id}");

    $response->assertNoContent();

    expect(Bot::find($bot->id))->toBeNull();
});
