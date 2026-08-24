<?php

use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Http\Controllers\BotController;
use TelegramBotEssentials\Essence\Http\Controllers\TelegramWebhookController;
use TelegramBotEssentials\Essence\Http\Middleware\AuthorizeAccessToBots;
use TelegramBotEssentials\Essence\Http\Middleware\InitializeTenancyByPath;
use TelegramBotEssentials\Essence\Http\Middleware\TelegramBotAuthentication;

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
