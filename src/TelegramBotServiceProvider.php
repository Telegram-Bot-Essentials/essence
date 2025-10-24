<?php

namespace TelegramBotEssentials\Essence;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Resolvers\PathTenantResolver;
use TelegramBotEssentials\Essence\Console\Commands\BotManagementTokenCommand;
use TelegramBotEssentials\Essence\Console\Commands\InitMainBotCommand;
use TelegramBotEssentials\Essence\Console\Commands\InstallCommand;
use TelegramBotEssentials\Essence\Console\Commands\MakeCallbackQuery;
use TelegramBotEssentials\Essence\Console\Commands\MakeCommand;
use TelegramBotEssentials\Essence\Console\Commands\MakeFeature;
use TelegramBotEssentials\Essence\Console\Commands\MakeReplyKey;
use TelegramBotEssentials\Essence\Console\Commands\MakeStateAnswer;
use TelegramBotEssentials\Essence\Console\Commands\PublishCommand;
use TelegramBotEssentials\Essence\Console\Commands\SetWebhook;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(TenancyServiceProvider::class);
        $this->app->register(\Stancl\Tenancy\TenancyServiceProvider::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/tbe-essence.php', 'tbe-essence');
        $this->mergeConfigFrom(__DIR__ . '/../config/tenancy.php', 'tenancy');
        $this->mergeConfigFrom(__DIR__ . '/../config/telegram.php', 'telegram');

        $this->initializeSingletons();
    }

    private function initializeSingletons(): void
    {
        $this->app->singleton(Webhook::class, fn() => new Webhook());

        $this->app->singleton(ReplyKeyBus::class, function ($app) {
            return new ReplyKeyBus();
        });

        $this->app->singleton(CallbackQueryBus::class, function ($app) {
            return new CallbackQueryBus();
        });

        $this->app->singleton(StateAnswerBus::class, function ($app) {
            return new StateAnswerBus();
        });

    }

    public function boot(): void
    {
        $this->loadConsoleRoutes();

        $this->registerCommands();
        $this->registerPublishing();

        BelongsToTenant::$tenantIdColumn = 'bot_id';
        PathTenantResolver::$tenantParameterName = 'bot';

        Route::prefix('api')
            ->group(__DIR__ . '/../routes/api.php');
        Route::prefix('')
            ->group(__DIR__ . '/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-essence');

        replyKeyBus()->addReplyKeys([
            CancelProcessKey::class
        ]);
    }

    protected function loadConsoleRoutes(): void
    {
        if ($this->app->runningInConsole()) {
            $consoleRoutes = __DIR__ . '/../routes/console.php';

            if (file_exists($consoleRoutes)) {
                require $consoleRoutes;
            }
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetWebhook::class,
                BotManagementTokenCommand::class,
                MakeReplyKey::class,
                MakeCallbackQuery::class,
                MakeStateAnswer::class,
                MakeFeature::class,
                MakeCommand::class,
                InstallCommand::class,
                PublishCommand::class,
                InitMainBotCommand::class
            ]);
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/tbe-essence.php' => config_path('tbe-essence.php'),
            ], 'tbe-essence-config');

            $this->publishes([
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-essence'),
            ], 'tbe-essence-translations');
        }
    }
}
