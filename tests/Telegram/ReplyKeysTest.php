<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Database\factories\BotFactory;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member\MainMenuKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    TelegramUser::factory()->create([
        'peer_id' => env('TELEGRAM_TEST_CHAT_ID'),
    ]);
    $bot = Bot::factory()->create([
        'bot_token' => env('TELEGRAM_TEST_BOT_TOKEN'),
        'bot_owner_peer_id' => env('TELEGRAM_TEST_CHAT_ID'),
        'activated_until' => null
    ]);

    $bot->settings->bot_status = true;
    $bot->settings->save();
});

test('Main Menu reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::main_menu.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});

test('Main Panel reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::admin_panel.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});

test('Bot Admins reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::bot_admins.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});

test('Bot Settings reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::bot_settings.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});
