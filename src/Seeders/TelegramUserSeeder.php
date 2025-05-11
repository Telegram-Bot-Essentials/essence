<?php

namespace Elyar\TelegramBotEssentials\Seeders;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Seeder;

class TelegramUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bot = Bot::firstOrCreate([
            'unique_id' => config('telegram-bot-essentials.develop.DEVELOP_UNIQUE_ID'),
            'bot_token' => config('telegram-bot-essentials.develop.DEVELOP_TELEGRAM_BOT_TOKEN'),
            'secret_token' => config('telegram-bot-essentials.develop.DEVELOP_SECRET_TOKEN')
        ]);

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.develop.DEVELOPER_CHAT_ID'),
        ]);

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_id' => $telegramUser->id,
            'state' => 'test',
            'power' => 100,
            'balance' => 1000000
        ]);

        if(config('telegram-bot-essentials.develop.TEST_USER_CHAT_ID') == null)
            return;
        
        $telegramTestUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.develop.TEST_USER_CHAT_ID'),
        ]);

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_id' => $telegramTestUser->id,
            'state' => 'test',
            'power' => 100,
            'balance' => 1000000
        ]);
    }
}
