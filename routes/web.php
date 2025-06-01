<?php

use Elyar\TelegramBotEssentials\Http\Controllers\BotController;
use Elyar\TelegramBotEssentials\Http\Controllers\GatewayZirgozarController;
use Elyar\TelegramBotEssentials\Http\Controllers\TelegramWebhookController;
use Elyar\TelegramBotEssentials\Http\Middleware\AuthorizeAccessToBots;
use Elyar\TelegramBotEssentials\Http\Middleware\InitializeTenancyByPath;
use Elyar\TelegramBotEssentials\Http\Middleware\TelegramBotAuthentication;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::prefix('invoice/{token}')->name('invoice.')->controller(GatewayZirgozarController::class)->group(function (){
    Route::prefix('zirgozar')->name('zirgozar.')->controller(GatewayZirgozarController::class)->group(function (){
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });
});