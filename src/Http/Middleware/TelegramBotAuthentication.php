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

class TelegramBotAuthentication
{
    use HttpResponses;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     * @throws WebhookAuthException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $unique_id = $request->route('unique_id');
        $bot = Bot::where('unique_id', $unique_id)->first();

        if(empty($bot))
            return $this->error('Invalid Bot unique ID', 404);

        wHook()::setBot($bot);

        if($request->header('x-telegram-bot-api-secret-token') !== $bot->secret_token)
            return $this->error('Unauthorized', 403);

        try {
            $api = new Api($bot->bot_token);
            wHook()::setApi($api);
            $update = wHook()->api()->getWebhookUpdate(request: new ServerRequest(
                method:'POST',
                uri: $request->url(),
                headers: $request->headers->all(),
                body: $request->getContent(),
                serverParams: $request->server->all()
            ));
            wHook()::setUpdate($update);
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage() ?? 'error message is not provided');
            Log::error($e->getTraceAsString() ?? 'Trace is not provided');
            return $this->error('Failed to initialize API service', 503);
        }

        wHook()::setUser($this->fetchUserData());

        return $next($request);
    }

    /**
     * @throws WebhookAuthException
     */
    private function fetchUserData(): BotUser
    {
        if (wHook()->update()->message) {
            $from = wHook()->update()->message->from;
        } elseif (wHook()->update()->callbackQuery) {
            $from = wHook()->update()->callbackQuery->from;
        }

        if(empty($from) || !($from instanceof User))
            throw new WebhookAuthException('Failed to retrieve telegram from.');

        $telegramUser = TelegramUser::updateOrCreate(
            ['peer_id' => $from->id],
            [
                'first_name' => $from->firstName,
                'last_name' => $from->lastName,
                'username' => $from->username,
            ]
        );

        $telegramUser->touch();
        return wHook()->bot()->botUsers()->firstOrCreate(['telegram_user_id' => $telegramUser->id]);
    }
}
