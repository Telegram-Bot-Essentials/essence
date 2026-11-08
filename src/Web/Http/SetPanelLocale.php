<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TelegramBotEssentials\Essence\Web\PanelLocales;

/** Panel pages speak the viewer's chosen language; the bot keeps its own. */
class SetPanelLocale
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(PanelLocales::current());

        return $next($request);
    }
}
