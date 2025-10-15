<?php

use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Http\Controllers\GatewayZarinpalController;
use TelegramBotEssentials\Essence\Http\Controllers\GatewayZirgozarController;

Route::prefix('invoice/{token}')->name('invoice.')->controller(GatewayZirgozarController::class)->group(function () {
    Route::prefix('zirgozar')->name('zirgozar.')->controller(GatewayZirgozarController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });

    Route::prefix('zarinpal')->name('zarinpal.')->controller(GatewayZarinpalController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });
});