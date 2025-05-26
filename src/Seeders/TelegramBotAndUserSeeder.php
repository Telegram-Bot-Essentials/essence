<?php

namespace Elyar\TelegramBotEssentials\Seeders;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Seeder;

class TelegramBotAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.develop.DEVELOPER_CHAT_ID'),
        ]);

        $bot = Bot::where('bot_token', config('telegram-bot-essentials.develop.DEVELOP_TELEGRAM_BOT_TOKEN'))->first();

        if(!$bot) {
            $bot = Bot::factory()->create([
                'bot_token' => config('telegram-bot-essentials.develop.DEVELOP_TELEGRAM_BOT_TOKEN'),
                'secret_token' => config('telegram-bot-essentials.develop.DEVELOP_SECRET_TOKEN'),
                'bot_owner_peer_id' => $telegramUser->peer_id,
                'activated_until' => now()->addDays(30),
                'suspended_at' => null,
            ]);
        }

        $bot->settings->bot_status = true;
        $bot->settings->pay_with_card = true;
        $bot->settings->transactions_chat_id = config('telegram-bot-essentials.develop.DEVELOP_TRANSACTIONS_CHAT_ID');
        $bot->settings->pay_to_card_number = config('telegram-bot-essentials.develop.DEVELOPER_CARD_NUMBER');
        $bot->settings->pay_to_card_name = config('telegram-bot-essentials.develop.DEVELOPER_CARD_NAME');
        $bot->settings->save();

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
            'state' => 'test',
            'power' => 100,
            'balance' => 1000000
        ]);

        if (config('telegram-bot-essentials.develop.TEST_USER_CHAT_ID') == null)
            return;

        $telegramTestUser = TelegramUser::firstOrCreate([
            'first_name' => 'test_user',
            'peer_id' => config('telegram-bot-essentials.develop.TEST_USER_CHAT_ID'),
        ]);

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramTestUser->peer_id,
            'state' => 'test',
            'power' => 100,
            'balance' => 1000000
        ]);
    }
}
