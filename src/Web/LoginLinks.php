<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * One-time browser sign-in links, sent to a user by their bot.
 *
 * @phpstan-type Link array{user: int|string, bot: int|string}
 */
final class LoginLinks
{
    public function create(BotUser $user, int $ttlSeconds = 120): string
    {
        $bot = $user->bot;
        abort_unless($bot instanceof Bot, 404);

        $token = Str::random(48);
        Cache::put($this->key($token), ['user' => $user->getKey(), 'bot' => $bot->getKey()], $ttlSeconds);

        return route('tbe.web.login.show', ['bot' => $bot->unique_id, 'token' => $token]);
    }

    /** @return Link|null */
    public function peek(string $token): ?array
    {
        /** @var Link|null */
        return Cache::get($this->key($token));
    }

    /** @return Link|null */
    public function consume(string $token): ?array
    {
        /** @var Link|null */
        return Cache::pull($this->key($token));
    }

    private function key(string $token): string
    {
        return 'tbe-web:login:'.hash('sha256', $token);
    }
}
