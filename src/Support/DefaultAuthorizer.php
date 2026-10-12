<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Database\Eloquent\Builder;
use Stancl\Tenancy\Database\TenantScope;
use TelegramBotEssentials\Essence\Contracts\AuthorizesAccess;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Essence's own access model, for bots that need no roles: a handler
 * without a permission is open to everyone, and one with a permission is
 * for the bot's owner and the configured developer only. A companion that
 * grants permissions to other users (tbe-access) rebinds AuthorizesAccess.
 */
class DefaultAuthorizer implements AuthorizesAccess
{
    public function allows(BotUser $user, ?string $permission): bool
    {
        return $permission === null || $this->isOwnerOrDeveloper($user);
    }

    /** Nobody holds a permission through a grant here. */
    public function usersWith(Bot $bot, string $permission): Builder
    {
        return BotUser::withoutGlobalScope(TenantScope::class)
            ->where('bot_id', $bot->getKey())
            ->whereRaw('1 = 0');
    }

    /**
     * The bot's owner or the configured developer: allowed everything under
     * any access model. Public so a role package's authorizer and its
     * escalation checks share one definition. No role tables are read.
     */
    public function isOwnerOrDeveloper(BotUser $user): bool
    {
        $peerId = self::int($user->getAttribute('telegram_user_peer_id'));

        if ($peerId === null) {
            return false;
        }

        // env() hands the developer id over as a string.
        if (self::int(config('tbe-essence.developer.peer_id')) === $peerId) {
            return true;
        }

        return self::int($this->botOf($user)?->getAttribute('bot_owner_peer_id')) === $peerId;
    }

    /** The webhook's bot when it is the user's, so no query per check. */
    private function botOf(BotUser $user): ?Bot
    {
        $botId = self::int($user->getAttribute('bot_id'));

        if (wHook()->check() && self::int(wHook()->bot()->getKey()) === $botId) {
            return wHook()->bot();
        }

        $bot = $user->bot;

        return $bot instanceof Bot ? $bot : null;
    }

    /** Database ids and env values arrive as int or numeric string. */
    private static function int(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : null;
    }
}
