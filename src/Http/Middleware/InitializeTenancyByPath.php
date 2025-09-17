<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use TelegramBotEssentials\Essence\Models\Bot;
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
