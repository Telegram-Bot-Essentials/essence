<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Symfony\Component\HttpFoundation\Response;
use TelegramBotEssentials\Essence\Models\Bot;

class InitializeTenancyByPath
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botUniqueId = $request->route()->parameter('bot');
        $bot = Bot::where('unique_id', $botUniqueId)->first();
        if (! $bot) {
            return tbeApiResponse()->error('Invalid Bot Unique ID', 204);
        }
        tenancy()->initialize($bot);

        return $next($request);
    }
}
