<?php

namespace TelegramBotEssentials\Essence\Web;

/**
 * Checks the signed string a Telegram Mini App receives as initData.
 *
 * @see https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
final class TelegramInitData
{
    /**
     * @return array<string, mixed>|null the decoded fields, or null when the
     *                                   signature is wrong or it is too old
     */
    public static function verify(string $initData, string $botToken, int $maxAgeSeconds): ?array
    {
        $fields = [];
        foreach (explode('&', $initData) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $fields[urldecode($key)] = urldecode($value);
        }

        $hash = $fields['hash'] ?? null;
        if (! is_string($hash) || $hash === '') {
            return null;
        }
        unset($fields['hash']);

        ksort($fields);
        $lines = [];
        foreach ($fields as $key => $value) {
            $lines[] = $key.'='.$value;
        }

        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);
        if (! hash_equals(hash_hmac('sha256', implode("\n", $lines), $secret), $hash)) {
            return null;
        }

        $age = time() - (int) ($fields['auth_date'] ?? 0);
        if ($age > $maxAgeSeconds || $age < -60) {
            return null;
        }

        if (isset($fields['user'])) {
            $fields['user'] = json_decode($fields['user'], true);
        }

        return $fields;
    }
}
