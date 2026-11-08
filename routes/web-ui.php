<?php

use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Http\Middleware\InitializeTenancyByPath;
use TelegramBotEssentials\Essence\Web\Http\LimitFraming;
use TelegramBotEssentials\Essence\Web\Http\PanelLocaleController;
use TelegramBotEssentials\Essence\Web\Http\SetPanelLocale;
use TelegramBotEssentials\Essence\Web\Http\WebAuthController;

Route::middleware(['web', LimitFraming::class])
    ->get('/tbe-web/locale/{locale}', PanelLocaleController::class)
    ->name('tbe.web.locale');

Route::middleware(['web', LimitFraming::class, SetPanelLocale::class, InitializeTenancyByPath::class])
    ->prefix('{bot}/web')
    ->name('tbe.web.')
    ->controller(WebAuthController::class)
    ->group(function () {
        Route::get('/open', 'open')->name('open');
        Route::get('/login/{token}', 'showLogin')->name('login.show');
        Route::post('/login/{token}', 'login')->middleware('throttle:30,1')->name('login');
    });
