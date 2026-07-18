<?php

namespace TelegramBotEssentials\Essence;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Resolvers\PathTenantResolver;
use TelegramBotEssentials\Essence\Console\Commands\BotManagementTokenCommand;
use TelegramBotEssentials\Essence\Console\Commands\InitMainBotCommand;
use TelegramBotEssentials\Essence\Console\Commands\InstallCommand;
use TelegramBotEssentials\Essence\Console\Commands\MakeCallbackQuery;
use TelegramBotEssentials\Essence\Console\Commands\MakeInlineQuery;
use TelegramBotEssentials\Essence\Console\Commands\MakeCommand;
use TelegramBotEssentials\Essence\Console\Commands\MakeFeature;
use TelegramBotEssentials\Essence\Console\Commands\MakeReplyKey;
use TelegramBotEssentials\Essence\Console\Commands\MakeStateAnswer;
use TelegramBotEssentials\Essence\Console\Commands\PublishCommand;
use TelegramBotEssentials\Essence\Console\Commands\SetWebhook;
use TelegramBotEssentials\Essence\Console\Commands\CheckMissingTranslations;
use TelegramBotEssentials\Essence\Console\Commands\TranslationStats;
use TelegramBotEssentials\Essence\Events\BotEventBus;
use TelegramBotEssentials\Essence\Services\TranslationScanner;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQueryBus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\HttpClients\LaravelHttpClient;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(TenancyServiceProvider::class);
        $this->app->register(\Stancl\Tenancy\TenancyServiceProvider::class);

        $this->app->singleton(LaravelHttpClient::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/tbe-essence.php', 'tbe-essence');
        $this->mergeConfigFrom(__DIR__ . '/../config/tenancy.php', 'tenancy');
        $this->mergeConfigFrom(__DIR__ . '/../config/telegram.php', 'telegram');

        $this->registerBotsManager();
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

        $this->app->singleton(BotEventBus::class, fn() => new BotEventBus());

        $this->app->singleton(TranslationScanner::class, fn() => new TranslationScanner());

        $this->app->singleton(InlineQueryBus::class, function ($app) {
            return new InlineQueryBus();
        });
    }

    private function registerBotsManager(): void
    {
        $this->app->singleton(BotsManager::class, function ($app) {
            $config = $app['config']->get('telegram');
            $handler = $config['http_client_handler'] ?? null;

            if (is_string($handler) && class_exists($handler)) {
                $config['http_client_handler'] = $app->make($handler);
            }

            return (new BotsManager($config))->setContainer($app);
        });
        $this->app->alias(BotsManager::class, 'telegram');
        $this->app->bind(Api::class, fn ($app) => $app[BotsManager::class]->bot());
        $this->app->alias(Api::class, 'telegram.bot');
    }

    public function boot(): void
    {
        // Stancl\Tenancy\TenancyServiceProvider is auto-discovered as its own
        // package and always registers before ours, so its config/config.php
        // defaults (tenant_model => Stancl's Tenant, bootstrappers including
        // DatabaseTenancyBootstrapper) win over our mergeConfigFrom call in
        // register() regardless of call order there. Force our values back on
        // top here, since boot() is guaranteed to run after every provider's
        // register() phase has completed.
        //
        // Read the raw file rather than hardcoding the override inline so this
        // stays a single source of truth, but replace central_connection: the
        // file resolves it via env('DB_CONNECTION'), and env() is unsafe to
        // call here once config is cached (production disables .env loading
        // on cached requests), so pull it from the already-cache-safe
        // database.default instead.
        $tenancyConfig = require __DIR__ . '/../config/tenancy.php';
        $tenancyConfig['database']['central_connection'] = $this->app['config']->get('database.default');

        $this->app['config']->set('tenancy', array_merge(
            $this->app['config']->get('tenancy', []),
            $tenancyConfig
        ));

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
                MakeInlineQuery::class,
                MakeStateAnswer::class,
                MakeFeature::class,
                MakeCommand::class,
                InstallCommand::class,
                PublishCommand::class,
                InitMainBotCommand::class,
                CheckMissingTranslations::class,
                TranslationStats::class,
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
