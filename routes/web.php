<?php

use Elyar\TelegramBotEssentials\Http\Controllers\GatewayZirgozarController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoice/{token}')->name('invoice.')->controller(GatewayZirgozarController::class)->group(function (){
    Route::prefix('zirgozar')->name('zirgozar.')->controller(GatewayZirgozarController::class)->group(function (){
        Route::get('/pay', 'pay')->name('pay');
        Route::get('/callback', 'callback')->name('callback');
    });
});