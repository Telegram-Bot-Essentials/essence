<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use TelegramBotEssentials\Essence\Models\Bot;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantExist
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botUniqueId = $request->route()->parameter('bot');
        Bot::where('unique_id', $botUniqueId)->firstOrFail();
        return $next($request);
    }
}
