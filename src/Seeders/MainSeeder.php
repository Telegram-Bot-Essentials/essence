<?php

namespace TelegramBotEssentials\Essence\Seeders;

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use Illuminate\Database\Seeder;

class MainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!config('tbe-essence.main.unique_id') ||
            !config('tbe-essence.main.telegram_bot_token') ||
            !config('tbe-essence.main.admin_chat_id') ||
            !config('tbe-essence.main.currency')
        ) {
            $this->command->info('Main bot is not configured');
            return;
        }

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('tbe-essence.main.admin_chat_id'),
        ]);

        $bot = Bot::where('unique_id', config('tbe-essence.main.unique_id'))->first();

        if (!$bot) {
            $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
            $bot = Bot::factory()->create([
                'bot_token' => config('tbe-essence.main.telegram_bot_token'),
                'unique_id' => config('tbe-essence.main.unique_id'),
                'currency' => config('tbe-essence.main.currency'),
                'secret_token' => $secretToken,
                'bot_owner_peer_id' => $telegramUser->peer_id,
                'activated_until' => null,
                'suspended_at' => null,
            ]);
        }

//        $bot->settings->bot_status = true;
//        $bot->settings->save();

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
            'power' => 100,
        ]);
    }
}
