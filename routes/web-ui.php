<?php

use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Http\Middleware\InitializeTenancyByPath;
use TelegramBotEssentials\Essence\Web\Http\LimitFraming;
use TelegramBotEssentials\Essence\Web\Http\WebAuthController;

// The sign-in script, served from the package so no publish step can go stale.
Route::get('/tbe-web/sign-in.js', fn () => response()->file(__DIR__.'/../resources/js/sign-in.js', [
    'Content-Type' => 'text/javascript',
    'Cache-Control' => 'no-cache',
]))->name('tbe.web.sign-in-script');

Route::middleware(['web', LimitFraming::class, InitializeTenancyByPath::class])
    ->prefix('{bot}/web')
    ->name('tbe.web.')
    ->controller(WebAuthController::class)
    ->group(function () {
        Route::get('/open', 'open')->name('open');
        Route::post('/auth/telegram', 'telegram')->middleware('throttle:30,1')->name('telegram');
        Route::get('/login/{token}', 'showLogin')->name('login.show');
        Route::post('/login/{token}', 'login')->middleware('throttle:30,1')->name('login');
    });
