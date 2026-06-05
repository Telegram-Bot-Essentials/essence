<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Traits\CanResolveBotCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetWebhook extends Command
{
    use CanResolveBotCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbe:set-webhook
         {--unique-id= : Enter the target bot unique id}
         {--endpoint= : Custom webhook endpoint (use {unique_id} placeholder)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the Telegram webhook for a bot';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $uniqueID = $this->option('unique-id') ?? config('tbe-essence.main.unique_id');
        $this->info('Setting webhook for bot with unique id: ' . $uniqueID);
        $this->info('Telegram bot api url: ' . config('tbe-essence.base_bot_url'));

        $endpointTemplate = $this->option('endpoint')
            ?? config('tbe-essence.webhook_endpoint', '/api/{unique_id}/telegram/bot/webhook');

        $url = rtrim(config('app.url'), '/') . str_replace('{unique_id}', $uniqueID, $endpointTemplate);
        $this->info('Webhook url: ' . $url);

        $bot = Bot::where('unique_id', $uniqueID)->first();

        if (!$bot) {
            $this->error('Bot with unique id: ' . $uniqueID . ' not found');
            return;
        }

        $telegram = telegramApi($bot->bot_token);

        $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');

        $bot->secret_token = Hash::make($secretToken);
        $bot->save();

        $telegram->setWebhook([
            'url' => $url,
            'drop_pending_updates' => true,
            'secret_token' => $secretToken,
        ]);

        $commands = [];
        foreach (config('tbe-essence.commands') as $command) {
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
