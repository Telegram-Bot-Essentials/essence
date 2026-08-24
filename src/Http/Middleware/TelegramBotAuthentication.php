<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Exceptions\WebhookAuthException;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class TelegramBotAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws WebhookAuthException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bot = tenancy()->tenant;

        if (empty($bot) || ! ($bot instanceof Bot)) {
            return tbeApiResponse()->error('Invalid Bot ID', 204);
        }

        wHook()->setBot($bot);

        if (! hash_equals((string) $bot->secret_token, (string) $request->header('x-telegram-bot-api-secret-token'))) {
            return tbeApiResponse()->error('Unauthorized', 204);
        }

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
            tbeLog('essence')->error('Failed to initialize Telegram API service: '.$e->getMessage(), ['exception' => $e]);

            return tbeApiResponse()->error('Failed to initialize API service', 503);
        }

        $botUser = $this->fetchUserData();
        wHook()->setUser($botUser);

        // An update can only reach us from someone who has not blocked the bot
        // and whose account still exists, so any inbound update other than the
        // membership change itself proves the user is reachable. This is what
        // recovers a user whose unblock event was lost to drop_pending_updates,
        // and what undoes a deactivation recorded by mistake.
        if (! wHook()->update()->myChatMember) {
            botUserStatus()->markActive($botUser);
        }

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
        } elseif (self::isPrivateChatMemberUpdate()) {
            $from = wHook()->update()->myChatMember->from;
        } else {
            throw new HttpResponseException(tbeApiResponse()->error('Invalid update', 204));
        }

        if (empty($from) || ! ($from instanceof User)) {
            throw new WebhookAuthException('Failed to retrieve telegram from.');
        }

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

    /**
     * my_chat_member also fires when the bot is added to or removed from a
     * group or channel, where "from" is the admin who did it rather than
     * someone in a conversation with the bot. Nothing here models group
     * membership, so those keep the old behaviour of being rejected outright
     * instead of minting a BotUser or rewriting that admin's status.
     */
    public static function isPrivateChatMemberUpdate(): bool
    {
        $chatMember = wHook()->update()->myChatMember;

        if (! $chatMember) {
            return false;
        }

        return $chatMember->chat?->type === 'private'
            && (int) $chatMember->chat?->id === (int) $chatMember->from?->id;
    }
}
