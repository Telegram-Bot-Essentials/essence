<?php

use Elyar\TelegramBotEssentials\Http\Controllers\GatewayZarinpalController;
use Elyar\TelegramBotEssentials\Http\Controllers\GatewayZibalController;
use Elyar\TelegramBotEssentials\Http\Controllers\GatewayZirgozarController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoice/{token}')->name('invoice.')->controller(GatewayZirgozarController::class)->group(function () {
    Route::prefix('zirgozar')->name('zirgozar.')->controller(GatewayZirgozarController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });

    Route::prefix('zarinpal')->name('zarinpal.')->controller(GatewayZarinpalController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });

    Route::prefix('zibal')->name('zibal.')->controller(GatewayZibalController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });
});