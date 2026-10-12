<?php

namespace TelegramBotEssentials\Essence\Testing;

use Illuminate\Database\Eloquent\Builder;
use Stancl\Tenancy\Database\TenantScope;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\DefaultAuthorizer;

/**
 * Essence's default access model plus per-user grants, for tests that need
 * a non-owner who holds a permission without installing a role package.
 * Bound by TestCase::grant().
 */
class GrantingAuthorizer extends DefaultAuthorizer
{
    /** @var array<int, list<string>> permission names by bot user id */
    private array $grants = [];

    public function grant(BotUser $user, string ...$permissions): void
    {
        $id = self::key($user);

        $this->grants[$id] = array_values(array_unique([...$this->grants[$id] ?? [], ...$permissions]));
    }

    public function allows(BotUser $user, ?string $permission): bool
    {
        return parent::allows($user, $permission)
            || in_array($permission, $this->grants[self::key($user)] ?? [], true);
    }

    public function usersWith(Bot $bot, string $permission): Builder
    {
        $ids = array_keys(array_filter($this->grants, fn (array $held) => in_array($permission, $held, true)));

        return BotUser::withoutGlobalScope(TenantScope::class)
            ->where('bot_id', $bot->getKey())
            ->whereKey($ids);
    }

    private static function key(BotUser $user): int
    {
        $key = $user->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }
}
