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

class AuthorizeAccessToBots
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
        if($request->header('Authorization') !== config('telegram-bot-essentials.bot_access.token'))
            return $this->error('Unauthorized access to the bot management', 403);

        return $next($request);
    }
}
