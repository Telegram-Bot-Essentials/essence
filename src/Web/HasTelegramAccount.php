<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * TelegramAccount for the app's user model, all but createForTelegram().
 * Only Laravel's Gate decides who may manage a bot, so it works with or
 * without any companion package.
 *
 * @phpstan-require-implements TelegramAccount
 */
trait HasTelegramAccount
{
    /** @var array<int, BotUser>|null kept for the request: the panel asks on every check */
    private ?array $panelBotUsersCache = null;

    /** Links outliving their account would keep its Telegram accounts from signing in. */
    public static function bootHasTelegramAccount(): void
    {
        static::deleted(function (self $account): void {
            TelegramLink::query()->where('account_id', $account->getKey())->delete();
        });
    }

    /** @return HasMany<TelegramLink, $this> */
    public function telegramLinks(): HasMany
    {
        return $this->hasMany(TelegramLink::class, 'account_id')->orderBy('id');
    }

    /** @return list<int> */
    public function linkedPeerIds(): array
    {
        return array_values($this->telegramLinks()->get()->map(fn (TelegramLink $link): int => $link->telegram_user_peer_id)->all());
    }

    public function linkTelegram(int $peerId): void
    {
        $existing = TelegramLink::query()->where('telegram_user_peer_id', $peerId)->first();
        if ($existing instanceof TelegramLink) {
            if ($existing->account_id === $this->accountKey()) {
                return;
            }
            // Its account is gone (deleted outside Eloquent): the Telegram account is free.
            if (static::query()->whereKey($existing->account_id)->exists()) {
                throw TelegramAccountTaken::peer($peerId);
            }
            $existing->delete();
        }

        try {
            $this->telegramLinks()->create(['telegram_user_peer_id' => $peerId]);
        } catch (UniqueConstraintViolationException) {
            throw TelegramAccountTaken::peer($peerId);
        }

        $this->panelBotUsersCache = null;
    }

    public function unlinkTelegram(int $peerId): void
    {
        $this->telegramLinks()->where('telegram_user_peer_id', $peerId)->delete();
        $this->panelBotUsersCache = null;
    }

    /** @return array<int, BotUser> */
    public function panelBotUsers(): array
    {
        if ($this->panelBotUsersCache !== null) {
            return $this->panelBotUsersCache;
        }

        $peers = $this->linkedPeerIds();
        $order = array_flip($peers);

        // Across bots: BotUser is tenant-scoped, and panel requests run in one
        // bot's tenancy, which Filament adds a scope of its own for once a bot is open.
        $candidates = BotUser::query()
            ->withoutGlobalScopes()
            ->whereIn('telegram_user_peer_id', $peers)
            ->with('bot')
            ->get()
            ->sortBy(function (BotUser $user) use ($order): array {
                $peer = $user->peerId();
                $owner = $user->bot?->getAttribute('bot_owner_peer_id');
                $isOwner = is_numeric($owner) && (int) $owner === $peer;

                return [$isOwner ? 0 : 1, $order[$peer] ?? PHP_INT_MAX];
            });

        $chosen = [];
        foreach ($candidates as $user) {
            $botId = $user->getAttribute('bot_id');
            $botId = is_numeric($botId) ? (int) $botId : 0;
            if (! isset($chosen[$botId]) && $user->bot instanceof Bot && $this->mayManage($user, $user->bot)) {
                $chosen[$botId] = $user;
            }
        }

        return $this->panelBotUsersCache = $chosen;
    }

    /**
     * Asked inside $bot's tenancy: ability rules may read tenant-scoped data,
     * and the panel asks about other bots than the one a request is in.
     */
    private function mayManage(BotUser $user, Bot $bot): bool
    {
        $previous = tenancy()->tenant;
        if ($previous instanceof Bot && $previous->getKey() === $bot->getKey()) {
            return Gate::forUser($user)->allows('web.manage');
        }

        tenancy()->initialize($bot);
        try {
            return Gate::forUser($user)->allows('web.manage');
        } finally {
            $previous instanceof Bot ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    private function accountKey(): int
    {
        $key = $this->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    public function actingBotUser(Bot $bot): ?BotUser
    {
        $key = $bot->getKey();

        return is_numeric($key) ? ($this->panelBotUsers()[(int) $key] ?? null) : null;
    }
}
