<?php

namespace TelegramBotEssentials\Essence\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Exceptions\WebhookAuthException;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
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

        // Essence does not model group chats yet: a group update would run
        // against the sender's private-chat state and reply in their DM.
        // Acknowledge and drop it before a BotUser is minted for a group
        // lurker.
        if (self::isFromNonPrivateChat()) {
            return tbeApiResponse()->success();
        }

        // Telegram redelivers an update whenever it does not see a prompt
        // response, and can otherwise send the same update_id twice. Claim it
        // atomically before doing any work: a duplicate finds the claim
        // already taken and is dropped without being reprocessed. The claim
        // is released on failure so a crashed handler still gets retried,
        // but kept on success so a slow-yet-successful run is never redone.
        $dedupKey = 'tbe:update:'.$bot->getKey().':'.$update->updateId;

        if (! Cache::add($dedupKey, true, config('tbe-essence.update_dedup.ttl_seconds', 300))) {
            return tbeApiResponse()->success();
        }

        try {
            $botUser = $this->fetchUserData();
            wHook()->setUser($botUser, self::conversationFor($botUser));

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
        } catch (\Throwable $e) {
            Cache::forget($dedupKey);

            throw $e;
        }
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
     * The conversation an update belongs to: its chat and forum topic. An
     * update without a chat (an inline query, a press on an inline-mode
     * message, a private my_chat_member) belongs to the private chat.
     */
    private static function conversationFor(BotUser $botUser): Conversation
    {
        $update = wHook()->update();
        $message = $update->message ?? $update->callbackQuery?->message;
        $chatId = $message?->chat?->id;

        if ($message === null || ! is_numeric($chatId)) {
            return $botUser->privateConversation();
        }

        $threadId = $message->get('is_topic_message') === true ? $message->get('message_thread_id') : 0;

        return $botUser->conversationIn((int) $chatId, is_numeric($threadId) ? (int) $threadId : 0);
    }

    /**
     * A message or button press in a group, supergroup or channel. Inline
     * queries have no chat, and a press on an inline-mode message has no
     * message, so both still go through.
     */
    public static function isFromNonPrivateChat(): bool
    {
        $update = wHook()->update();
        $message = $update->message ?? $update->callbackQuery?->message;
        $chat = $message?->chat;

        return $chat !== null && $chat->type !== 'private';
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
