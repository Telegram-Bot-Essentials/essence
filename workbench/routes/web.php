<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use TelegramBotEssentials\Essence\Web\Filament\PanelUser;

Route::get('/', fn () => view('welcome'));

// Workbench only: sign in as the demo owner and simulate Telegram's hash. These
// skip all sign-in, so they exist only on the port no tunnel points at. An IP
// check would not do: tunnelled requests arrive from 127.0.0.1 too.
if (($_SERVER['SERVER_PORT'] ?? null) !== '8791') {
    return;
}

Route::middleware('web')->group(function () {
    Route::get('/dev/login/{to}', function (string $to) {
        Auth::guard('tbe-web')->login(PanelUser::query()->where('telegram_user_peer_id', 111)->firstOrFail());

        return redirect('/'.$to);
    })->where('to', '.*');

    Route::get('/dev/telegram/{scheme}/{panel}', function (string $scheme, string $panel) {
        $fields = ['auth_date' => (string) time(), 'query_id' => 'AAA', 'user' => json_encode(['id' => 111, 'first_name' => 'Elyar'])];
        ksort($fields);
        $check = collect($fields)->map(fn ($v, $k) => "$k=$v")->implode("\n");
        $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', '1:demo', 'WebAppData', true));
        $theme = $scheme === 'dark'
            ? ['bg_color' => '#17212b', 'secondary_bg_color' => '#232e3c', 'section_bg_color' => '#17212b', 'text_color' => '#f5f5f5', 'hint_color' => '#708499', 'link_color' => '#6ab3f3', 'button_color' => '#5288c1', 'button_text_color' => '#ffffff', 'section_header_text_color' => '#6ab3f3', 'section_separator_color' => '#111921', 'destructive_text_color' => '#ec3942', 'header_bg_color' => '#17212b']
            : ['bg_color' => '#ffffff', 'secondary_bg_color' => '#f1f1f6', 'section_bg_color' => '#ffffff', 'text_color' => '#000000', 'hint_color' => '#8e8e93', 'link_color' => '#c2185b', 'button_color' => '#c2185b', 'button_text_color' => '#ffffff', 'section_header_text_color' => '#c2185b', 'section_separator_color' => '#e5e5ea', 'destructive_text_color' => '#e53935', 'header_bg_color' => '#ffffff'];

        return redirect("/demo/web/open?panel=$panel#tgWebAppData=".rawurlencode(http_build_query($fields, '', '&', PHP_QUERY_RFC3986)).'&tgWebAppVersion=8.0&tgWebAppPlatform=android&tgWebAppThemeParams='.rawurlencode(json_encode($theme)));
    });
});
