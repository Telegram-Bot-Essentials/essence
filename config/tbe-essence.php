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
];
