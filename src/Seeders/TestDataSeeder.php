<?php

namespace TelegramBotEssentials\Essence\Seeders;

use TelegramBotEssentials\Essence\Models\Bot;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    private Bot $bot;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->bot = Bot::first();
        $this->setTestSettings();
    }

    private function setTestSettings(): void
    {
        $this->bot->settings->bot_status = true;
        $this->bot->settings->wallet = true;
        $this->bot->settings->pay_with_card = true;
        $this->bot->settings->pay_to_card_name = fake()->name();
        $this->bot->settings->pay_to_card_number = fake()->numberBetween(1111222233334444, 9999888877773333);
        $this->bot->settings->transactions_chat_id = config('telegram-bot-essentials.main.admin_chat_id') ?? config('telegram-bot-essentials.develop.DEVELOPER_CHAT_ID') ?? null;
        $this->bot->settings->save();
    }
}
