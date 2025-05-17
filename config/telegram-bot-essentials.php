<?php

use Telegram\Bot\Commands\HelpCommand;

return [
    'keyboard' => [
        'admin' => [

        ],
        'member' => [

        ]
    ],

    'commands' => [
        HelpCommand::class,
    ],

    'bot_access' => [
        'token' => env('BOT_MANAGEMENT_ACCESS_TOKEN'),
    ],

    'supported_currencies' => [
        ['name' => 'USD', 'symbol' => '$'],
        ['name' => 'IRR', 'symbol' => 'ریال'],
        ['name' => 'IRT', 'symbol' => 'تومان'],
    ],

    'develop' => [
    'DEVELOP_UNIQUE_ID' => env('DEVELOP_UNIQUE_ID'),
    'DEVELOP_TELEGRAM_BOT_TOKEN' => env('DEVELOP_TELEGRAM_BOT_TOKEN'),
    'DEVELOP_SECRET_TOKEN' => env('DEVELOP_SECRET_TOKEN'),
    'DEVELOPER_CHAT_ID' => env('DEVELOPER_CHAT_ID'),
    'TEST_USER_CHAT_ID' => env('TEST_USER_CHAT_ID'),

    'DEVELOPER_CARD_NUMBER' => env('DEVELOPER_CARD_NUMBER'),
    'DEVELOPER_CARD_NAME' => env('DEVELOPER_CARD_NAME'),
    'DEVELOP_TRANSACTIONS_CHAT_ID' => env('DEVELOP_TRANSACTIONS_CHAT_ID'),
]
];
