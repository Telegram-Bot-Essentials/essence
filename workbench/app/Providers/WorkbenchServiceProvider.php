<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Models\BotUser;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Essence defines no abilities, so the Gate denies every handler that
        // names one. Let the bot's owner through, as an app would.
        Gate::before(fn (mixed $user) => $user instanceof BotUser
            && (string) $user->telegram_user_peer_id === (string) $user->bot?->bot_owner_peer_id ? true : null);
    }
}
