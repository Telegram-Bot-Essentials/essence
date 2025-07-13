<?php

namespace Elyar\TelegramBotEssentials\Http\Middleware;

use Closure;
use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByPath
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botUniqueId = $request->route()->parameter('bot');
        $bot = Bot::where('unique_id', $botUniqueId)->firstOrFail();
        tenancy()->initialize($bot);
        return $next($request);
    }
}
