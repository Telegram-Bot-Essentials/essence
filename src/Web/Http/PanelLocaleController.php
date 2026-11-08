<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TelegramBotEssentials\Essence\Web\PanelLocales;

class PanelLocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(isset(PanelLocales::available()[$locale]), 404);

        $request->session()->put(PanelLocales::SESSION_KEY, $locale);

        return redirect()->back(fallback: url('/'));
    }
}
