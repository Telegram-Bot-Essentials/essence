<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAccessToBots
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
        if ($request->header('Authorization') !== config('telegram-bot-essentials.bot_access.token'))
            return apiResponse()->error('Unauthorized access to the bot management', 403);

        return $next($request);
    }
}
