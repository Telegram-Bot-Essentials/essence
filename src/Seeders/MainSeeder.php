<?php

namespace Elyar\TelegramBotEssentials\Seeders;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Exception;
use Illuminate\Database\Seeder;
use Telegram\Bot\Api;

class MainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!config('telegram-bot-essentials.main.UNIQUE_ID') ||
            !config('telegram-bot-essentials.main.TELEGRAM_BOT_TOKEN') ||
            !config('telegram-bot-essentials.main.ADMIN_CHAT_ID')
        ) return;

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.main.ADMIN_CHAT_ID'),
        ]);

        $bot = Bot::where('unique_id', config('telegram-bot-essentials.main.UNIQUE_ID'))->first();

        if (!$bot) {
            $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
            $bot = Bot::factory()->create([
                'bot_token' => config('telegram-bot-essentials.main.TELEGRAM_BOT_TOKEN'),
                'unique_id' => config('telegram-bot-essentials.main.UNIQUE_ID'),
                'secret_token' => $secretToken,
                'bot_owner_peer_id' => $telegramUser->peer_id,
                'activated_until' => null,
                'suspended_at' => null,
            ]);
        }

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
            'power' => 100,
        ]);

        try{
            $api = new Api($bot->bot_token);
            $api->setWebhook([
                'url' => config('app.url') . "/api/{$bot->unique_id}/telegram/bot/webhook",
                'secret_token' => $bot->secret_token,
            ]);
        } catch (Exception){

        }
    }
}
