<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('telegram', 'bot_admins');

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

test('Bot Admins reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::bot_admins.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});

//it('Can add and remove admin', function () {
//    $bot = Bot::first();
//
//    $bot->refresh();
//
//    $response = postJson(
//        "/api/{$bot->unique_id}/telegram/bot/webhook",
//        callbackQuery(
//            encodeCallback('BOTADMNS', ['add_admin'])
//        ),
//        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
//    );
//
//    $response->assertOk();
//
//    $telegramUser = TelegramUser::factory()->create();
//
//    $response = postJson(
//        "/api/{$bot->unique_id}/telegram/bot/webhook",
//        message($telegramUser->peer_id),
//        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
//    );
//
//    $response->assertOk();
//    assert($bot->botUsers->where(['telegram_user_peer_id' => $telegramUser->peer_id, 'power' => Roles::ADMIN->value])->exists());
//});

