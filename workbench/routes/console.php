<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

// Point the demo bot at a real throwaway test bot, from a gitignored file.
Artisan::command('demo:use-bot', function () {
    $values = parse_ini_file(__DIR__.'/../.test-bot.env') ?: [];
    $token = trim((string) ($values['TEST_BOT_TOKEN'] ?? ''));
    $peer = (int) ($values['TEST_PEER_ID'] ?? 0);

    if ($token === '' || $peer === 0) {
        $this->error('Fill TEST_BOT_TOKEN and TEST_PEER_ID in workbench/.test-bot.env first.');

        return 1;
    }

    TelegramUser::query()->firstOrCreate(
        ['peer_id' => $peer],
        TelegramUser::factory()->raw(['peer_id' => $peer]),
    );
    $bot = Bot::query()->where('unique_id', 'demo')->firstOrFail();
    $bot->update(['bot_token' => $token, 'bot_owner_peer_id' => $peer]);
    BotUser::query()->firstOrCreate(['bot_id' => $bot->id, 'telegram_user_peer_id' => $peer]);

    $this->info("Demo bot updated. Owner is $peer. The token was not printed.");

    return 0;
});
