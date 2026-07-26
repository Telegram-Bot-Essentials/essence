<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use TelegramBotEssentials\Essence\Exceptions\WebhookAuthException;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class TelegramBotAuthentication
{
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
        $bot = tenancy()->tenant;

        if (empty($bot) || !($bot instanceof Bot))
            return apiResponse()->error('Invalid Bot ID', 204);

        wHook()->setBot($bot);

        if (!hash_equals((string) $bot->secret_token, (string) $request->header('x-telegram-bot-api-secret-token')))
            return apiResponse()->error('Unauthorized', 204);

        try {
            $api = telegramApi($bot->bot_token);
            wHook()->setApi($api);
            $update = wHook()->api()->getWebhookUpdate(request: new ServerRequest(
                method: 'POST',
                uri: $request->url(),
                headers: $request->headers->all(),
                body: $request->getContent(),
                serverParams: $request->server->all()
            ));
            wHook()->setUpdate($update);
        } catch (TelegramSDKException $e) {
            tbeLog('essence')->error('Failed to initialize Telegram API service: ' . $e->getMessage(), ['exception' => $e]);
            return apiResponse()->error('Failed to initialize API service', 503);
        }

        wHook()->setUser($this->fetchUserData());

        if ($context = WebhookContext::capture()) {
            botEventBus()->fire(new BotWebhookInitialized($context));
        }

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
        } elseif (wHook()->update()->inlineQuery) {
            $from = wHook()->update()->inlineQuery->from;
        } else {
            throw new HttpResponseException(apiResponse()->error('Invalid update', 204));
        }

        if (empty($from) || !($from instanceof User))
            throw new WebhookAuthException('Failed to retrieve telegram from.');

        $telegramUser = TelegramUser::updateOrCreate(
            ['peer_id' => $from->id],
            [
                'first_name' => $from->firstName,
                'last_name' => $from->lastName,
                'username' => $from->username,
            ]
        );

        $botUser = BotUser::firstOrCreate(['telegram_user_peer_id' => $telegramUser->peer_id]);
        $botUser->interact();
        return $botUser;
    }
}
