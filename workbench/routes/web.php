<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Web\TelegramLink;
use Workbench\App\Models\User;

Route::get('/', fn () => view('welcome'));

// Workbench only: sign in as the demo owner. This
// skips all sign-in, so it exists only on the port no tunnel points at. An IP
// check would not do: tunnelled requests arrive from 127.0.0.1 too.
if (($_SERVER['SERVER_PORT'] ?? null) !== '8791') {
    return;
}

Route::middleware('web')->group(function () {
    Route::get('/dev/login/{to}', function (string $to) {
        $account = TelegramLink::accountFor(111);
        if ($account === null) {
            $account = User::createForTelegram(TelegramUser::query()->where('peer_id', 111)->firstOrFail());
            $account->linkTelegram(111);
        }
        Auth::guard('tbe-web')->login($account);

        return redirect('/'.$to);
    })->where('to', '.*');

});
