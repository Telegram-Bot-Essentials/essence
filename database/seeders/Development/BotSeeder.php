<?php

namespace Elyar\TelegramBotEssentials\Database\Seeders\Development;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Database\Seeder;

class BotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bot = Bot::firstOrCreate([
            'unique_id' => config('develop.DEVELOP_UNIQUE_ID'),
            'bot_token' => config('develop.DEVELOP_TELEGRAM_BOT_TOKEN'),
            'secret_token' => config('develop.DEVELOP_SECRET_TOKEN')
        ]);

        $bot->settings->bot_status = true;
        $bot->settings->pay_with_card = true;
        $bot->settings->pay_to_card_number = config('develop.DEVELOPER_CARD_NUMBER');
        $bot->settings->pay_to_card_name = config('develop.DEVELOPER_CARD_NAME');
        $bot->settings->transactions_chat_id = config('develop.DEVELOP_TRANSACTIONS_CHAT_ID');
        $bot->settings->save();

        Bot::firstOrCreate([
            'unique_id' => 'test',
            'bot_token' => 'test',
            'secret_token' => 'test'
        ]);
    }
}
