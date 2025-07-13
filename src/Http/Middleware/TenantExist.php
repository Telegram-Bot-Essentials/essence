<?php

namespace Elyar\TelegramBotEssentials\Http\Middleware;

use Closure;
use Elyar\TelegramBotEssentials\Models\Bot;
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
