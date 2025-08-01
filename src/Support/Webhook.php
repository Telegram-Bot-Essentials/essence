<?php

namespace Elyar\TelegramBotEssentials\Support;

use Closure;
use Elyar\TelegramBotEssentials\Exceptions\WebhookAuthException;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class Webhook
{
    private static ?Api $api = null;
    private static ?Update $update = null;
    private static ?Bot $bot = null;
    private static ?BotUser $user = null;
    private static ?string $requestState = null;

    public static function setApi(?Api $api): void
    {
        self::$api = $api;
    }

    public static function setUpdate(?Update $update): void
    {
        self::$update = $update;
    }

    public static function setBot(?Bot $bot): void
    {
        self::$bot = $bot;
    }

    public static function setUser(?BotUser $user): void
    {
        self::$user = $user;
        self::$requestState = $user->state;
    }

    public static function api(): Api
    {
        try {
            if (self::$api == null) throw new WebhookAuthException('Failed to retrieve API service.');
            return self::$api;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public static function update(): Update
    {
        try {
            if (self::$update == null) throw new WebhookAuthException('Failed to retrieve Updates.');
            return self::$update;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public static function bot(): Bot
    {
        try {
            if (self::$bot == null) throw new WebhookAuthException('Failed to retrieve bot.');
            return self::$bot;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public static function user(): BotUser
    {
        try {
            if (self::$user == null) throw new WebhookAuthException('Failed to retrieve telegram user.');
            return self::$user;
        } catch (WebhookAuthException $e) {
            exceptionHandler()->handle($e);
            abort(200, $e->getMessage());
        }
    }

    public static function requestState(): ?string
    {
        return self::$requestState;
    }

    public static function check(): bool
    {
        return self::$bot !== null
            && self::$user !== null
            && self::$api !== null
            && self::$update !== null;
    }

    public static function runForUser(BotUser $user, Closure $callback): mixed
    {
        $originalBot = self::$bot;
        $originalUser = self::$user;

        self::setBot($user->bot);
        self::setUser($user);

        try {
            \Log::error('yeah');
            return $callback();
        } finally {
            self::setBot($originalBot);
            self::setUser($originalUser);
        }
    }
}
