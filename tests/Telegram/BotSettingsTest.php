<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('telegram', 'bot_settings');

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

test('Bot Settings reply key works fine', function () {
    $bot = Bot::first();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message(__('tbe::bot_settings.reply_key')),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
});

it('Can change bot status', function () {
    $bot = Bot::first();

    $toggle = function () use (&$bot) {
        $bot->refresh();
        $oldStatus = $bot->settings->bot_status;

        $response = postJson(
            "/api/{$bot->unique_id}/telegram/bot/webhook",
            callbackQuery("BTSTNG?bot_status&" . !$oldStatus),
            ['x-telegram-bot-api-secret-token' => $bot->secret_token]
        );

        $response->assertOk();
        $bot->refresh();

        expect($bot->settings->bot_status)->toBe(!$oldStatus);
    };

    $toggle();
    $toggle();
});

//it('Can change bot currency', function () {
//    $bot = Bot::first();
//
//    $toggle = function () use (&$bot) {
//        $bot->refresh();
//
//        $oldCurrency = $bot->settings->default_currency;
//        $nextCurrency = getNextFromArray(getSupportedCurrencies(), $oldCurrency);
//        $response = postJson(
//            "/api/{$bot->unique_id}/telegram/bot/webhook",
//            callbackQuery(encodeCallback('BTSTNG', ['bot_currency',
//                $nextCurrency])),
//            ['x-telegram-bot-api-secret-token' => $bot->secret_token]
//        );
//
//        $response->assertOk();
//        $bot->refresh();
//
//        expect($bot->settings->default_currency)->toBe($nextCurrency);
//    };
//
//    foreach (getSupportedCurrencies() as $currency) {
//        $toggle();
//    }
//});

it('Can change bot language', function () {
    $bot = Bot::first();

    $bot->refresh();

    $oldLanguage = $bot->settings->language;
    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        callbackQuery(encodeCallback('BTSTNG', ['bot_language'])),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
    $bot->refresh();

    expect($bot->settings->language)->not->toBe($oldLanguage);
});

it('Can change bot transactions chat id', function () {
    $bot = Bot::first();

    $bot->refresh();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        callbackQuery(encodeCallback('BTSTNG', ['change_transactions_chat_id'])),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();

    $fakeChatId = fake()->randomNumber();
    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message($fakeChatId),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
    $bot->settings->refresh();

    expect($bot->settings->transactions_chat_id)->toBe($fakeChatId);
});

it('Can change bot card number', function () {
    $bot = Bot::first();

    $bot->refresh();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        callbackQuery(encodeCallback('BTSTNG', ['change_payment_card_number'])),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();

    $fakeCardNumber = fake()->creditCardNumber();
    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message($fakeCardNumber),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
    $bot->settings->refresh();

    expect($bot->settings->pay_to_card_number)->toBe($fakeCardNumber);
});

it('Can change bot card name', function () {
    $bot = Bot::first();

    $bot->refresh();

    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        callbackQuery(encodeCallback('BTSTNG', ['change_payment_card_name'])),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();

    $fakeCardName = fake()->name();
    $response = postJson(
        "/api/{$bot->unique_id}/telegram/bot/webhook",
        message($fakeCardName),
        ['x-telegram-bot-api-secret-token' => $bot->secret_token]
    );

    $response->assertOk();
    $bot->settings->refresh();

    expect($bot->settings->pay_to_card_name)->toBe($fakeCardName);
});

