<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class setWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbe:set-webhook
         {--unique-id= : Enter the target bots unique id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws TelegramSDKException
     */
    public function handle()
    {
        $uniqueID = $this->option('unique-id') ?? config('telegram-bot-essentials.develop.DEVELOP_UNIQUE_ID');
        if ($uniqueID) {
            $bot = Bot::where('unique_id', $uniqueID)->firstOrFail();
            $telegram = new Api($bot->bot_token);
            $telegram->setWebhook([
                'url' => config('app.url') . "/api/{$uniqueID}/telegram/bot/webhook",
                'drop_pending_updates' => true,
                'secret_token' => $bot->secret_token,
            ]);
            $this->info('Telegram webhook is set');
        }
    }
}
