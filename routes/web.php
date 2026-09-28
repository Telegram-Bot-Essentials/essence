<?php

use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Http\Controllers\GatewayZarinpalController;

Route::prefix('invoice/{token}')->name('invoice.')->group(function () {
    Route::prefix('zarinpal')->name('zarinpal.')->controller(GatewayZarinpalController::class)->group(function () {
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });
});
