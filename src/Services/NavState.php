<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Objects\Message;

/**
 * Small pieces of navigation context (current page, sort, etc.) that a
 * screen needs to build a "back" button for whatever edited this same
 * message. Cached against the message itself instead of round-tripping
 * through callback_data, which is capped at 64 bytes by Telegram.
 */
class NavState
{
    private const TTL_SECONDS = 60 * 60 * 24 * 7;

    public function put(int|string $chatId, int $messageId, array $data): void
    {
        Cache::put($this->key($chatId, $messageId), $data, self::TTL_SECONDS);
    }

    public function get(int|string $chatId, int $messageId, array $default = []): array
    {
        return Cache::get($this->key($chatId, $messageId), $default);
    }

    public function putForCurrentMessage(array $data): void
    {
        $message = $this->currentMessage();

        if (! $message) {
            return;
        }

        $this->put($message->chat->id, $message->messageId, $data);
    }

    public function getForCurrentMessage(array $default = []): array
    {
        $message = $this->currentMessage();

        if (! $message) {
            return $default;
        }

        return $this->get($message->chat->id, $message->messageId, $default);
    }

    private function currentMessage(): ?Message
    {
        return wHook()->update()->callbackQuery?->message ?? wHook()->update()->message;
    }

    private function key(int|string $chatId, int $messageId): string
    {
        return "tbe:nav_state:{$chatId}:{$messageId}";
    }
}
