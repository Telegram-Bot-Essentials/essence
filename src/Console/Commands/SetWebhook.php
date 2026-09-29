<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Support\BotCommandMenu;
use Throwable;

class SetWebhook extends Command
{
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
    public function handle(): int
    {
        $endpointTemplate = $this->option('endpoint')
            ?? config('tbe-essence.webhook_endpoint', '/api/{unique_id}/telegram/bot/webhook');

        if ($this->option('all')) {
            $bots = Bot::all();

            if ($bots->isEmpty()) {
                $this->error('No bots found');

                return self::FAILURE;
            }

            $failed = 0;

            // One bot with a revoked token must not stop the rest.
            foreach ($bots as $bot) {
                try {
                    $this->rotateWebhook($bot, $endpointTemplate);
                } catch (Throwable $e) {
                    $failed++;
                    $this->error('Failed for bot '.$bot->unique_id.': '.$e->getMessage());
                }
            }

            if ($failed > 0) {
                $this->error($failed.' of '.$bots->count().' bots failed');
            }

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $uniqueID = $this->option('unique-id') ?? config('tbe-essence.main.unique_id');

        $bot = Bot::where('unique_id', $uniqueID)->first();

        if (! $bot) {
            $this->error('Bot with unique id: '.$uniqueID.' not found');

            return self::FAILURE;
        }

        $this->rotateWebhook($bot, $endpointTemplate);

        return self::SUCCESS;
    }

    private function rotateWebhook(Bot $bot, string $endpointTemplate): void
    {
        $this->info('Setting webhook for bot with unique id: '.$bot->unique_id);
        $this->info('Telegram bot api url: '.config('tbe-essence.base_bot_url'));

        $url = rtrim(config('app.url'), '/').str_replace('{unique_id}', $bot->unique_id, $endpointTemplate);
        $this->info('Webhook url: '.$url);

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

        app(BotCommandMenu::class)->register($bot);

        $this->info('Telegram webhook has been set for '.$bot->unique_id);
        $this->info('Bot url: https://t.me/'.$telegram->getMe()->username);
    }
}
