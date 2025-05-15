<?php

use Elyar\TelegramBotEssentials\Http\Controllers\BotController;
use Elyar\TelegramBotEssentials\Http\Controllers\TelegramWebhookController;
use Elyar\TelegramBotEssentials\Http\Middleware\AuthorizeAccessToBots;
use Elyar\TelegramBotEssentials\Http\Middleware\TelegramBotAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/telegram/bot/{unique_id}/webhook', function (){
    return response('OK',200);
});

Route::post('/telegram/bot/{unique_id}/webhook', TelegramWebhookController::class)->middleware(TelegramBotAuthentication::class);

Route::prefix('bots')->middleware(AuthorizeAccessToBots::class)->controller(BotController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}', 'show')->name('show');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
});
