<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

uses(RefreshDatabase::class);

it('runs the package migrations', function () {
    foreach ([
        'telegram_users',
        'bots',
        'bot_users',
        'message_metas',
        'inline_confirmations',
        'state_data',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing table: {$table}");
    }
});

it('builds a bot through its factory', function () {
    $bot = Bot::factory()->create();

    expect($bot->exists)->toBeTrue()
        ->and($bot->unique_id)->not->toBeEmpty()
        ->and($bot->suspended)->toBeFalse();
});

it('hides the secret token when serialising a bot', function () {
    expect(Bot::factory()->create()->toArray())->not->toHaveKey('secret_token');
});

it('scopes bot users to their bot', function () {
    $telegramUser = TelegramUser::factory()->create();
    $botA = Bot::factory()->create(['bot_owner_peer_id' => $telegramUser->peer_id]);
    $botB = Bot::factory()->create(['bot_owner_peer_id' => $telegramUser->peer_id]);

    BotUser::factory()->create([
        'bot_id' => $botA->id,
        'telegram_user_peer_id' => $telegramUser->peer_id,
    ]);

    expect(BotUser::where('bot_id', $botA->id)->count())->toBe(1)
        ->and(BotUser::where('bot_id', $botB->id)->count())->toBe(0);
});

it('reports a bot as suspended once suspended_at is set', function () {
    $bot = Bot::factory()->create();
    expect($bot->suspended)->toBeFalse();

    $bot->update(['suspended_at' => now()]);

    expect($bot->fresh()->suspended)->toBeTrue();
});
