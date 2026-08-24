<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TelegramBotEssentials\Essence\Models\Bot;

class TenantExist
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botUniqueId = $request->route()->parameter('bot');
        Bot::where('unique_id', $botUniqueId)->firstOrFail();

        return $next($request);
    }
}
