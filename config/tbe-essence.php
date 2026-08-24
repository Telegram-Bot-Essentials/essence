<?php

use TelegramBotEssentials\Essence\Telegram\Commands\Member\HelpCommand;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\AdminPanelKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\MainMenuKey;

return [
    'keyboard' => [
        'admin' => [
            [MainMenuKey::class],
        ],
        'member' => [
            [AdminPanelKey::class],
        ],
    ],

    'commands' => [
        HelpCommand::class,
    ],

    'bot_access' => [
        'token' => env('BOT_MANAGEMENT_ACCESS_TOKEN'),
    ],

    'main' => [
        'unique_id' => env('MAIN_UNIQUE_ID', 'main'),
        'telegram_bot_token' => env('MAIN_TELEGRAM_BOT_TOKEN'),
        'admin_chat_id' => env('MAIN_ADMIN_CHAT_ID'),
        'currency' => env('MAIN_CURRENCY', 'USD'),
    ],

    'base_bot_url' => env('TELEGRAM_BOT_BASE_URL', 'https://api.telegram.org/bot'),

    'bug_report' => [
        'telegram_chat_id' => env('BUG_REPORT_TELEGRAM_CHAT_ID'),
    ],

    'logging' => [
        // Log channel for all TBE packages; null uses the app's default channel.
        'channel' => env('TBE_LOG_CHANNEL'),
        // Also push debug/bug reports to the bug_report Telegram chat.
        'telegram_notify' => env('TBE_LOG_TELEGRAM_NOTIFY', true),
    ],

    'developer' => [
        'peer_id' => env('DEVELOPER_PEER_ID'),
    ],

    'translation_stats' => [
        'base_locale' => 'en',
        'cache_key' => 'tbe.translation_stats',
        'ttl' => 86400,
    ],

    'pruning' => [
        'state_data_days' => env('TBE_PRUNE_STATE_DATA_DAYS', 7),
        'message_metas_days' => env('TBE_PRUNE_MESSAGE_METAS_DAYS', 7),
        'inline_confirmations_hours' => env('TBE_PRUNE_INLINE_CONFIRMATIONS_HOURS', 6),
    ],

    'routes' => [
        'enabled' => env('TBE_ROUTES_ENABLED', true),
        // Prefix for the bot management + webhook routes (routes/api.php).
        // Empty string mounts them at the app's root, same as before this
        // was configurable.
        'api_prefix' => env('TBE_ROUTES_API_PREFIX', 'api'),
        // Prefix for the payment-gateway callback routes (routes/web.php).
        'web_prefix' => env('TBE_ROUTES_WEB_PREFIX', ''),
    ],

    // update_id dedup requires an atomic Cache::add across workers/processes.
    // The array and file drivers do not provide that (array is per-worker
    // under Octane; file's add() is a non-atomic read-then-write), so both
    // let a redelivered update slip through. Use redis, memcached, or
    // another driver with a real atomic add in production.
    'update_dedup' => [
        'ttl_seconds' => env('TBE_UPDATE_DEDUP_TTL_SECONDS', 300),
    ],
];
