<?php

namespace TelegramBotEssentials\Essence\Seeders;

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use Illuminate\Database\Seeder;

class TelegramBotAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!config('tbe-essence.develop.DEVELOPER_CHAT_ID') ||
            !config('tbe-essence.develop.DEVELOP_TELEGRAM_BOT_TOKEN') ||
            !config('tbe-essence.develop.DEVELOP_SECRET_TOKEN') ||
            !config('tbe-essence.develop.DEVELOP_TRANSACTIONS_CHAT_ID') ||
            !config('tbe-essence.develop.DEVELOPER_CARD_NUMBER') ||
            !config('tbe-essence.develop.DEVELOPER_CARD_NAME')
        )
            return;

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('tbe-essence.develop.DEVELOPER_CHAT_ID'),
        ]);

        $bot = Bot::where('bot_token', config('tbe-essence.develop.DEVELOP_TELEGRAM_BOT_TOKEN'))->first();

        if (!$bot) {
            $bot = Bot::factory()->create([
                'bot_token' => config('tbe-essence.develop.DEVELOP_TELEGRAM_BOT_TOKEN'),
                'unique_id' => config('tbe-essence.develop.DEVELOP_UNIQUE_ID'),
                'secret_token' => config('tbe-essence.develop.DEVELOP_SECRET_TOKEN'),
                'bot_owner_peer_id' => $telegramUser->peer_id,
                'activated_until' => now()->addDays(30),
                'suspended_at' => null,
            ]);
        }

        $bot->settings->bot_status = true;
        $bot->settings->pay_with_card = true;
        $bot->settings->transactions_chat_id = config('tbe-essence.develop.DEVELOP_TRANSACTIONS_CHAT_ID');
        $bot->settings->pay_to_card_number = config('tbe-essence.develop.DEVELOPER_CARD_NUMBER');
        $bot->settings->pay_to_card_name = config('tbe-essence.develop.DEVELOPER_CARD_NAME');
        $bot->settings->save();

        $botUser = BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
        ]);

        $botUser->update([
            'state' => 'test',
            'power' => 100,
            'balance' => 1000000
        ]);

        if (config('tbe-essence.develop.TEST_USER_CHAT_ID') == null)
            return;

        $telegramTestUser = TelegramUser::firstOrCreate([
            'first_name' => 'test_user',
            'peer_id' => config('tbe-essence.develop.TEST_USER_CHAT_ID'),
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
