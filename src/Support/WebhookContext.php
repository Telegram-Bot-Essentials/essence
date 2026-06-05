<?php

namespace TelegramBotEssentials\Essence\Support;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class WebhookContext
{
    public function __construct(
        public readonly ?int $botId,
        public readonly ?int $botUserId,
        public readonly array|Update $updatePayload = [],
        public readonly ?string $botToken = null,
        public readonly ?Bot $bot = null,
        public readonly ?BotUser $botUser = null,
    ) {
    }

    public static function capture(): ?self
    {
        if (!wHook()->check()) {
            return null;
        }

        try {
            $bot = wHook()->bot();
            $botUser = wHook()->user();
            $update = wHook()->update();
        } catch (\Throwable) {
            return null;
        }

        return new self(
            botId: $bot?->getKey(),
            botUserId: $botUser?->getKey(),
            updatePayload: $update?->toArray() ?? [],
            botToken: $bot?->bot_token,
            bot: $bot,
            botUser: $botUser,
        );
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            botId: $payload['bot_id'] ?? null,
            botUserId: $payload['bot_user_id'] ?? null,
            updatePayload: $payload['update'] ?? [],
            botToken: $payload['bot_token'] ?? null,
            bot: $payload['bot'] ?? null,
            botUser: $payload['bot_user'] ?? null,
        );
    }

    public function resolveBot(): ?Bot
    {
        if ($this->bot instanceof Bot) {
            return $this->bot;
        }

        if ($this->botId === null) {
            return null;
        }

        return Bot::find($this->botId);
    }

    public function resolveBotUser(): ?BotUser
    {
        if ($this->botUser instanceof BotUser) {
            return $this->botUser;
        }

        if ($this->botUserId === null) {
            return null;
        }

        return BotUser::find($this->botUserId);
    }

    public function resolveUpdate(): Update
    {
        if ($this->updatePayload instanceof Update) {
            return $this->updatePayload;
        }

        if (empty($this->updatePayload)) {
            return new Update([]);
        }

        return new Update($this->updatePayload);
    }

    public function toArray(): array
    {
        return [
            'bot_id' => $this->botId,
            'bot_user_id' => $this->botUserId,
            'bot_token' => $this->botToken,
            'update' => $this->updatePayload,
            'bot' => $this->bot,
            'bot_user' => $this->botUser,
        ];
    }

    public function apply(): bool
    {
        wHook()->clear();

        return wHook()->importContext($this);
    }

    public function apiToken(): ?string
    {
        if ($this->botToken) {
            return $this->botToken;
        }

        return $this->resolveBot()?->bot_token;
    }

    public function api(): ?Api
    {
        $token = $this->apiToken();

        return $token ? telegramApi($token) : null;
    }
}
