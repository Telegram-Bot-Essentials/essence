<?php

namespace Elyar\TelegramBotEssentials\Http\Middleware;

use Closure;
use Elyar\TelegramBotEssentials\Exceptions\WebhookAuthException;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Elyar\TelegramBotEssentials\Traits\HttpResponses;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;

class TenantExist
{
    use HttpResponses;

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
