<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Models\BotUser;
use Workbench\App\Web\DemoAdminPlugin;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        webPanels()->manage(DemoAdminPlugin::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workbench');

        // Behind the HTTPS tunnel, or every asset URL is blocked as mixed content.
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // Essence defines no abilities, so the Gate denies every handler that
        // names one. Let the bot's owner through, as an app would.
        Gate::before(fn (mixed $user) => $user instanceof BotUser
            && (string) $user->telegram_user_peer_id === (string) $user->bot?->bot_owner_peer_id ? true : null);
    }
}
