<?php

namespace Elyar\TelegramBotEssentials\Http\Middleware;

use Closure;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Traits\HttpResponses;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByPath
{
    use HttpResponses;

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
//        echo 'bot unique' . "\n";
//        echo json_encode($botUniqueId) . "\n\n";
        $bot = Bot::where('unique_id', $botUniqueId)->firstOrFail();
//        echo 'bot resolved' . "\n";
//        echo json_encode($bot) . "\n\n";
        tenancy()->initialize($bot);
//        echo 'initialized tenancy' . "\n";
//        echo json_encode(tenancy()) . "\n\n";
        return $next($request);
    }
}
