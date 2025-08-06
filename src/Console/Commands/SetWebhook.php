<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Traits\CanResolveBotCommand;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\CommandInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SetWebhook extends Command
{
    use CanResolveBotCommand;
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
        $uniqueID = $this->option('unique-id') ?? config('telegram-bot-essentials.main.unique_id');
        $this->info('Setting webhook for bot with unique id: ' . $uniqueID);
        $this->info('Telegram bot api url: ' . config('telegram-bot-essentials.base_bot_url'));

        $url = config('app.url') . "/api/{$uniqueID}/telegram/bot/webhook";
        $this->info('Webhook url: ' . $url);
        $bot = Bot::where('unique_id', $uniqueID)->first();

        if (!$bot) {
            $this->error('Bot with unique id: ' . $uniqueID . ' not found');
            return;
        }

        $telegram = new Api(
            token: $bot->bot_token,
            baseBotUrl: config('telegram-bot-essentials.base_bot_url')
        );
        $telegram->setWebhook([
            'url' => $url,
            'drop_pending_updates' => true,
            'secret_token' => $bot->secret_token,
        ]);

        $commands = [];
        foreach(config('telegram-bot-essentials.commands') as $command){
            $command = $this->resolveBotCommand($command);
            $commands[] = [
                'command' => $command->getName(),
                'description' => $command->getDescription(),
            ];
        }

        $telegram->setMyCommands([
            'commands' => $commands,
            'scope' => [
                'type' => 'all_private_chats',
            ],
        ]);
        $this->info('Telegram webhook has been set');
    }
}
