<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * One-time codes that link another Telegram account to a signed-in web
 * account. The browser asks for one; the Telegram account being added opens
 * the bot with it (/start tbe-link-<code>) and confirms there.
 */
final class LinkCodes
{
    /** Telegram start payloads allow [A-Za-z0-9_-], 64 characters at most. */
    public const PAYLOAD_PREFIX = 'tbe-link-';

    public const TTL_SECONDS = 600;

    public function create(TelegramAccount $account): string
    {
        $code = Str::random(32);
        Cache::put($this->key($code), $account->getAuthIdentifier(), self::TTL_SECONDS);

        return $code;
    }

    /** The account a code links to, while it is still valid. */
    public function peek(string $code): ?TelegramAccount
    {
        return $this->account(Cache::get($this->key($code)));
    }

    public function consume(string $code): ?TelegramAccount
    {
        return $this->account(Cache::pull($this->key($code)));
    }

    /** What the bot shows so the person knows which account they join. */
    public static function accountName(TelegramAccount $account): string
    {
        if ($account instanceof Model) {
            foreach (['name', 'email'] as $attribute) {
                $value = $account->getAttribute($attribute);
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        $id = $account->getAuthIdentifier();

        return '#'.(is_scalar($id) ? $id : '');
    }

    private function account(mixed $id): ?TelegramAccount
    {
        if (! is_int($id) && ! is_string($id)) {
            return null;
        }

        $model = WebAccounts::model();
        $account = $model::query()->find($id);

        return $account instanceof TelegramAccount ? $account : null;
    }

    private function key(string $code): string
    {
        return 'tbe-web:link:'.hash('sha256', $code);
    }
}
