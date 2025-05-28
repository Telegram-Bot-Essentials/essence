<?php

use Dotenv\Dotenv;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

//use Illuminate\Foundation\Testing\RefreshDatabase;
//
//uses(RefreshDatabase::class);
/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Elyar\TelegramBotEssentials\Tests\TestCase::class)
    // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Telegram');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

$envPath = dirname(__DIR__); // adjust if needed
if (file_exists($envPath . '/.env.testing')) {
    Dotenv::createImmutable($envPath, '.env.testing')->load();
}

/**
 * @return Api
 * @throws TelegramSDKException
 */
function api(): Api {
    return new Api(env('TELEGRAM_TEST_BOT_TOKEN'));
}

function message(string $text = 'Main Menu 🔰'): array
{
    $chatId = env('TELEGRAM_TEST_CHAT_ID');
    $messageId = rand(10000, 100000);

    return [
        "update_id" => rand(10000, 100000),
        "message" => [
            "message_id" => $messageId,
            "from" => [
                "id" => $chatId,
                "is_bot" => false,
                "first_name" => "ELYAR",
                "username" => "Elyar_rr",
                "language_code" => "en",
            ],
            "chat" => [
                "id" => $chatId,
                "first_name" => "ELYAR",
                "username" => "Elyar_rr",
                "type" => "private",
            ],
            "date" => now()->timestamp,
            "text" => $text,
        ],
    ];
}

function command(string $text = '/start'): array
{
    $chatId = env('TELEGRAM_TEST_CHAT_ID');
    $messageId = rand(10000, 100000);

    return [
        "update_id" => rand(10000, 100000),
        "message" => [
            "message_id" => $messageId,
            "from" => [
                "id" => $chatId,
                "is_bot" => false,
                "first_name" => "ELYAR",
                "username" => "Elyar_rr",
                "language_code" => "en",
            ],
            "chat" => [
                "id" => $chatId,
                "first_name" => "ELYAR",
                "username" => "Elyar_rr",
                "type" => "private",
            ],
            "date" => now()->timestamp,
            "text" => $text,
            "entities" => [
                [
                    "offset" => 0,
                    "length" => strlen(trim($text)),
                    "type" => "bot_command"
                ]
            ],
        ],
    ];
}

function callbackQuery(string $data = "BTSTNG?bot_status&1"): array
{
    $chatId = env('TELEGRAM_TEST_CHAT_ID');
    preg_match('/^\d+/', env('TELEGRAM_TEST_BOT_TOKEN'), $matches);
    $botId = $matches[0] ?? rand(10000, 100000);

    $result = api()->sendMessage([
        'chat_id' => $chatId,
        'text' => 'test',
    ]);

    $messageId = $result->messageId;
    return [
        "update_id" => rand(10000, 100000),
        "callback_query" => [
            "id" => "4261716429142386678",
            "from" => [
                "id" => $chatId,
                "is_bot" => false,
                "first_name" => "ELYAR",
                "username" => "Elyar_rr",
                "language_code" => "en",
            ],
            "message" => [
                "message_id" => $messageId,
                "from" => [
                    "id" => $botId,
                    "is_bot" => true,
                    "first_name" => "telBot",
                    "username" => "telBot",
                ],
                "chat" => [
                    "id" => $chatId,
                    "first_name" => "ELYAR",
                    "username" => "Elyar_rr",
                    "type" => "private",
                ],
                "date" => rand(10000, 100000),
                "edit_date" => rand(10000, 100000),
                "text" => "message_text",
            ],
            "chat_instance" => "-1552160730376182465",
            "data" => $data,
        ],
    ];

}
