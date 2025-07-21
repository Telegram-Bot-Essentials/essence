<?php

use Elyar\TelegramBotEssentials\Http\Controllers\BotController;
use Elyar\TelegramBotEssentials\Http\Controllers\TelegramWebhookController;
use Elyar\TelegramBotEssentials\Http\Middleware\AuthorizeAccessToBots;
use Elyar\TelegramBotEssentials\Http\Middleware\InitializeTenancyByPath;
use Elyar\TelegramBotEssentials\Http\Middleware\TelegramBotAuthentication;
use Illuminate\Support\Facades\Route;


Route::prefix('bots')->middleware(AuthorizeAccessToBots::class)->controller(BotController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}', 'show')->name('show');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('put-update');
    Route::patch('/{id}', 'update')->name('patch-update');
    Route::delete('/{id}', 'destroy')->name('destroy');
});

Route::group([
    'prefix' => '/{bot}',
    'middleware' => InitializeTenancyByPath::class,
], function () {
    Route::get('/telegram/bot/webhook', function () {
        return response('OK', 200);
    });
    Route::post('/telegram/bot/webhook', TelegramWebhookController::class)->middleware(TelegramBotAuthentication::class);
});

Route::get('test', function () {

    return apiResponse()->success();
//    \Elyar\TelegramBotEssentials\Models\Billing\Invoice::factory()->count(40)->create();
//    return response('OK', 200);
//    $bot = \Elyar\TelegramBotEssentials\Models\Bot::where('unique_id', config('telegram-bot-essentials.develop.DEVELOP_UNIQUE_ID'))->first();
//    \Elyar\TelegramBotEssentials\Models\BotUser::factory()->count(200)->create([
//        'bot_id' => $bot->id,
//    ]);
//    return response(\Elyar\TelegramBotEssentials\Models\BotUser::count(), 200);
});