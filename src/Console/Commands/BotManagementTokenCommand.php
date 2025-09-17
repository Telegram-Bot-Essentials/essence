<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;
use Str;

class BotManagementTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbe:bot-management-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a random token for bot management access';

    public function handle()
    {
        $appName = config('app.name', 'TBE');
        $token = "$appName:" . Str::random(32);

        $this->info("Generated token: {$token}");
        $this->updateEnv('BOT_MANAGEMENT_ACCESS_TOKEN', $token);
        return 0;
    }

    protected function updateEnv($key, $value): bool
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return false;

        $escaped = preg_quote($key, '/');
        $envContent = file_get_contents($envPath);

        if (preg_match("/^{$escaped}=.*/m", $envContent)) {
            $envContent = preg_replace(
                "/^{$escaped}=.*/m",
                "{$key}=\"{$value}\"",
                $envContent
            );
        } else {
            $envContent .= PHP_EOL . "{$key}=\"{$value}\"" . PHP_EOL;
        }

        return file_put_contents($envPath, $envContent) !== false;
    }
}
