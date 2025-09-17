<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class InitMainBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     *   --force   Reinitialize bot even if it already exists
     */
    protected $signature = 'tbe:singlebot:init {--force : Reinitialize even if bot already exists}';

    /**
     * The console command description.
     */
    protected $description = 'Initialize the singlebot based on config values.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $required = [
            'unique_id',
            'telegram_bot_token',
            'admin_chat_id',
        ];

        foreach ($required as $key) {
            if (!config("telegram-bot-essentials.main.$key")) {
                $this->error("Missing configuration: telegram-bot-essentials.main.$key");
                return self::FAILURE;
            }
        }

        $telegramUser = TelegramUser::firstOrCreate([
            'peer_id' => config('telegram-bot-essentials.main.admin_chat_id'),
        ]);

        $bot = Bot::where('unique_id', config('telegram-bot-essentials.main.unique_id'))->first();

        if ($bot && !$this->option('force')) {
            $this->warn("Bot already exists. Use --force to reinitialize.");
        } else {
            $bot = Bot::updateOrCreate([
                'unique_id' => config('telegram-bot-essentials.main.unique_id'),
            ], [
                'bot_token' => config('telegram-bot-essentials.main.telegram_bot_token'),
                'bot_owner_peer_id' => $telegramUser->peer_id,
            ]);

            $this->info("Bot created successfully");
        }

        BotUser::firstOrCreate([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
        ]);

        $this->info("Admin user linked to bot");

        return self::SUCCESS;
    }
}
