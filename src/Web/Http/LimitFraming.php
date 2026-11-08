<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** No other site may frame these pages. */
class LimitFraming
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");

        return $response;
    }
}
