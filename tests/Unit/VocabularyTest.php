<?php

declare(strict_types=1);

/*
 * Essence knows two kinds of people: the bot's owner (bot_owner_peer_id) and
 * the developer. Anything else about who may do what is a Gate ability the
 * app defines. Admins, roles, staff, power levels and permissions belong to
 * the app or a companion package, so their names stay out of essence.
 */

const BANNED_WORDS = '/\b(admins?|roles?|staff|power|permissions?)\b/i';

/** Files that may use the words, and why. */
const VOCABULARY_EXCEPTIONS = [
    // The Telegram SDK's own sample config, shipped as it is.
    'config/telegram.php',
    // History: these created or changed the columns essence once had.
    'database/migrations/2025_04_01_121918_create_bot_users_table.php',
    'database/migrations/2026_10_11_120000_make_bot_users_menu_a_string.php',
    'database/migrations/2026_10_12_120000_drop_power_from_bot_users.php',
];

it('keeps access vocabulary out of essence', function () {
    $root = dirname(__DIR__, 2);
    $found = [];

    foreach (['src', 'config', 'lang', 'database'] as $dir) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $path = substr($file->getPathname(), strlen($root) + 1);
            if (in_array($path, VOCABULARY_EXCEPTIONS, true)) {
                continue;
            }

            foreach (file($file->getPathname()) ?: [] as $number => $line) {
                // A companion's package name is not a role.
                $line = str_replace('gateway-admin', '', $line);

                if (preg_match(BANNED_WORDS, $line, $match)) {
                    $found[] = "$path:".($number + 1).": {$match[0]}";
                }
            }
        }
    }

    expect($found)->toBe([]);
});
