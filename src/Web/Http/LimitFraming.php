<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Telegram Web shows a Mini App in an iframe; no other site may frame these pages. */
class LimitFraming
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /** @var list<string> $ancestors */
        $ancestors = config('tbe-essence.web.frame_ancestors', ["'self'"]);
        $response->headers->set('Content-Security-Policy', 'frame-ancestors '.implode(' ', $ancestors));

        return $response;
    }
}
