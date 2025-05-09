<?php

namespace Elyar\TelegramBotEssentials;

use Illuminate\Support\ServiceProvider;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register commands here
        $this->commands([
            \Elyar\TelegramBotEssentials\Console\Commands\MakeReplyKey::class,
        ]);
    }

    public function boot()
    {
        // Optional: load routes, migrations, translations, etc.
    }
}
