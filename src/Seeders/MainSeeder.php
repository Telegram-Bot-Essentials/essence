<?php

namespace Elyar\TelegramBotEssentials\Seeders;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Seeder;

class MainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!config('telegram-bot-essentials.main.unique_id') ||
            !config('telegram-bot-essentials.main.telegram_bot_token') ||
            !config('telegram-bot-essentials.main.admin_chat_id') ||
            !config('telegram-bot-essentials.main.currency')
        ) {
            $this->command->info('Main bot is not configured');
            return;
        }

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.main.admin_chat_id'),
        ]);

        $bot = Bot::where('unique_id', config('telegram-bot-essentials.main.unique_id'))->first();

        if (!$bot) {
            $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
            $bot = Bot::factory()->create([
                'bot_token' => config('telegram-bot-essentials.main.telegram_bot_token'),
                'unique_id' => config('telegram-bot-essentials.main.unique_id'),
                'currency' => config('telegram-bot-essentials.main.currency'),
                'secret_token' => $secretToken,
                'bot_owner_peer_id' => $telegramUser->peer_id,
                'activated_until' => null,
                'suspended_at' => null,
            ]);
        }

        $bot->settings->bot_status = true;
        $bot->settings->save();

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
            'power' => 100,
        ]);
    }
}
