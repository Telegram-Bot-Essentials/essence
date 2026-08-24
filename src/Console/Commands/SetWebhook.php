<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Traits\CanResolveCommand;
use Illuminate\Console\Command;

class SetWebhook extends Command
{
    use CanResolveCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbe:set-webhook
         {--unique-id= : Enter the target bot unique id}
         {--endpoint= : Custom webhook endpoint (use {unique_id} placeholder)}
         {--all : Rotate the webhook secret for every bot}';

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
        $endpointTemplate = $this->option('endpoint')
            ?? config('tbe-essence.webhook_endpoint', '/api/{unique_id}/telegram/bot/webhook');

        if ($this->option('all')) {
            $bots = Bot::all();

            if ($bots->isEmpty()) {
                $this->error('No bots found');
                return;
            }

            foreach ($bots as $bot) {
                $this->rotateWebhook($bot, $endpointTemplate);
            }

            return;
        }

        $uniqueID = $this->option('unique-id') ?? config('tbe-essence.main.unique_id');

        $bot = Bot::where('unique_id', $uniqueID)->first();

        if (!$bot) {
            $this->error('Bot with unique id: ' . $uniqueID . ' not found');
            return;
        }

        $this->rotateWebhook($bot, $endpointTemplate);
    }

    private function rotateWebhook(Bot $bot, string $endpointTemplate): void
    {
        $this->info('Setting webhook for bot with unique id: ' . $bot->unique_id);
        $this->info('Telegram bot api url: ' . config('tbe-essence.base_bot_url'));

        $url = rtrim(config('app.url'), '/') . str_replace('{unique_id}', $bot->unique_id, $endpointTemplate);
        $this->info('Webhook url: ' . $url);

        $telegram = telegramApi($bot->bot_token);

        $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');

        $bot->secret_token = $secretToken;
        $bot->save();

        $telegram->deleteWebhook();
        telegramApi($bot->bot_token, 'https://api.telegram.org/bot')->deleteWebhook();

        $telegram->setWebhook([
            'url' => $url,
            'drop_pending_updates' => true,
            'secret_token' => $secretToken,
        ]);

        $commands = [];
        foreach (config('tbe-essence.commands') as $command) {
            $command = $this->resolveCommand($command);
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

        $this->info('Telegram webhook has been set for ' . $bot->unique_id);
    }
}
