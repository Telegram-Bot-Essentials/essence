<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TelegramBotEssentials\Essence\Web\PanelCalendar;

class PanelCalendarController extends Controller
{
    public function __invoke(Request $request, string $calendar): RedirectResponse
    {
        abort_unless(in_array($calendar, PanelCalendar::available(), true), 404);

        $request->session()->put(PanelCalendar::SESSION_KEY, $calendar);

        return redirect()->back(fallback: url('/'));
    }
}
