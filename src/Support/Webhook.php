<?php

namespace TelegramBotEssentials\Essence\Support;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Exceptions\WebhookAuthException;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use Throwable;

class Webhook
{
    private ?Api $api = null;

    private ?Update $update = null;

    private ?Bot $bot = null;

    private ?BotUser $user = null;

    private ?Conversation $conversation = null;

    private ?string $requestState = null;

    public function clear(): void
    {
        $this->api = null;
        $this->update = null;
        $this->bot = null;
        $this->user = null;
        $this->conversation = null;
        $this->requestState = null;
    }

    public function setApi(?Api $api): void
    {
        $this->api = $api;
    }

    public function setUpdate(?Update $update): void
    {
        $this->update = $update;
    }

    public function api(): Api
    {
        try {
            if ($this->api == null) {
                throw new WebhookAuthException('Failed to retrieve API service.');
            }

            return $this->api;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public function update(): Update
    {
        try {
            if ($this->update == null) {
                throw new WebhookAuthException('Failed to retrieve Updates.');
            }

            return $this->update;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public function bot(): Bot
    {
        try {
            if ($this->bot == null) {
                throw new WebhookAuthException('Failed to retrieve bot.');
            }

            return $this->bot;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public function user(): BotUser
    {
        try {
            if ($this->user == null) {
                throw new WebhookAuthException('Failed to retrieve telegram user.');
            }

            return $this->user;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    /**
     * Where the update came from: the user in this chat (and forum topic).
     * State and the reply menu live here.
     */
    public function conversation(): Conversation
    {
        try {
            if ($this->conversation == null) {
                throw new WebhookAuthException('Failed to retrieve conversation.');
            }

            return $this->conversation;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public function chatId(): int
    {
        return (int) $this->conversation()->chat_id;
    }

    /** The forum topic, or null outside one. */
    public function threadId(): ?int
    {
        $threadId = (int) $this->conversation()->thread_id;

        return $threadId === 0 ? null : $threadId;
    }

    /** The conversation's state when the update arrived, before any handler changed it. */
    public function requestState(): ?string
    {
        return $this->requestState;
    }

    public function check(): bool
    {
        return $this->bot !== null
            && $this->user !== null
            && $this->conversation !== null
            && $this->api !== null
            && $this->update !== null;
    }

    public function runForUser(BotUser $user, Closure $callback): mixed
    {
        $originalBot = $this->bot;
        $originalUser = $this->user;
        $originalConversation = $this->conversation;
        // setUser() derives requestState from the conversation's state,
        // which is a problem for the restore below: if $callback changes
        // the original conversation (the exact object, not just an equal
        // row) - e.g. a state-answer handler that changes state before
        // triggering an event whose listener runs a payment for that same
        // user through here - re-deriving after the callback would read
        // the changed state instead of what it was before the swap.
        // Snapshot the value itself so the restore is a real restore.
        $originalRequestState = $this->requestState;

        // Acting on a user from outside their update: their private chat.
        self::setBot($user->bot);
        self::setUser($user);

        try {
            $result = $callback();
        } catch (Throwable $e) {
            exceptionHandler()->handle($e);
        }

        $this->bot = $originalBot;
        $this->user = $originalUser;
        $this->conversation = $originalConversation;
        $this->requestState = $originalRequestState;

        return $result ?? null;
    }

    public function setBot(?Bot $bot): void
    {
        $this->bot = $bot;
    }

    /**
     * Bind the user and the conversation the update belongs to; without a
     * conversation, the user's private chat.
     */
    public function setUser(?BotUser $user, ?Conversation $conversation = null): void
    {
        $this->user = $user;
        $this->conversation = $user === null ? null : ($conversation ?? $user->privateConversation());
        $this->requestState = $this->conversation?->state;
    }

    public function exportContext(): ?WebhookContext
    {
        if (! self::check()) {
            return null;
        }

        return new WebhookContext(
            botId: $this->bot?->getKey(),
            botUserId: $this->user?->getKey(),
            chatId: $this->conversation?->chat_id,
            threadId: $this->conversation?->thread_id,
            updatePayload: $this->update?->toArray() ?? [],
            botToken: $this->bot?->bot_token,
            bot: $this->bot,
            botUser: $this->user,
        );
    }

    public function importContext(WebhookContext|array|null $context): bool
    {
        if (empty($context)) {
            return false;
        }

        if ($context instanceof WebhookContext) {
            $snapshot = $context;
        } else {
            $snapshot = WebhookContext::fromArray($context);
        }

        $bot = $snapshot->bot ?? $snapshot->resolveBot();

        if (! $bot) {
            return false;
        }

        $botUser = $snapshot->botUser ?? $snapshot->resolveBotUser();

        if (! $botUser) {
            return false;
        }

        self::setBot($bot);
        // A context queued before conversations existed has no chat: fall
        // back to the user's private chat, which is where it came from.
        self::setUser($botUser, $snapshot->chatId === null
            ? null
            : $botUser->conversationIn($snapshot->chatId, $snapshot->threadId ?? 0));

        $providedApi = null;

        if (is_array($context) && isset($context['api']) && $context['api'] instanceof Api) {
            $providedApi = $context['api'];
        }

        if ($providedApi) {
            self::setApi($providedApi);
        } else {
            $token = $snapshot->botToken ?? $bot->bot_token;

            if (! $token) {
                return false;
            }
            self::setApi(telegramApi($token));
        }

        $updatePayload = $snapshot->updatePayload;

        if (is_array($context) && array_key_exists('update', $context)) {
            $updatePayload = $context['update'];
        }

        if ($updatePayload instanceof Update) {
            self::setUpdate($updatePayload);
        } elseif (is_array($updatePayload)) {
            self::setUpdate(new Update($updatePayload));
        } else {
            self::setUpdate($snapshot->resolveUpdate());
        }

        return self::check();
    }

    public function peerId(): int
    {
        if ($this->update->isType('callback_query')) {
            return $this->update->callbackQuery->from->id;
        }

        if ($this->update->isType('message')) {
            return $this->update->message->from->id;
        }

        if ($this->update->isType('inline_query')) {
            return $this->update->inlineQuery->from->id;
        }

        if (isset($this->update->chat)) {
            return $this->update->chat->id;
        }

        throw new HttpResponseException(tbeApiResponse()->error('Failed to retrieve telegram peer id.', 204));
    }
}
