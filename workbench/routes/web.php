<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Web\Filament\PanelUser;

Route::get('/', fn () => view('welcome'));

// Workbench only: sign in as the demo owner. This
// skips all sign-in, so it exists only on the port no tunnel points at. An IP
// check would not do: tunnelled requests arrive from 127.0.0.1 too.
if (($_SERVER['SERVER_PORT'] ?? null) !== '8791') {
    return;
}

Route::middleware('web')->group(function () {
    Route::get('/dev/login/{to}', function (string $to) {
        Auth::guard('tbe-web')->login(PanelUser::query()->where('telegram_user_peer_id', 111)->firstOrFail());

        return redirect('/'.$to);
    })->where('to', '.*');

});
