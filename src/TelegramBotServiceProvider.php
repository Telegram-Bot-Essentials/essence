<?php

namespace Elyar\TelegramBotEssentials;

use Elyar\TelegramBotEssentials\Console\Commands\BotManagementTokenCommand;
use Elyar\TelegramBotEssentials\Console\Commands\MakeCallbackQuery;
use Elyar\TelegramBotEssentials\Console\Commands\MakeCommand;
use Elyar\TelegramBotEssentials\Console\Commands\MakeFeature;
use Elyar\TelegramBotEssentials\Console\Commands\MakeReplyKey;
use Elyar\TelegramBotEssentials\Console\Commands\MakeStateAnswer;
use Elyar\TelegramBotEssentials\Console\Commands\setWebhook;
use Elyar\TelegramBotEssentials\Services\ApiResponse;
use Elyar\TelegramBotEssentials\Services\Billing;
use Elyar\TelegramBotEssentials\Services\Currency;
use Elyar\TelegramBotEssentials\Services\Gateways\Gateways;
use Elyar\TelegramBotEssentials\Services\Gateways\Wallet;
use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\ZarinPal;
use Elyar\TelegramBotEssentials\Services\Gateways\Zibal\Zibal;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQueryBus;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKeyBus;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswerBus;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Resolvers\PathTenantResolver;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(TenancyServiceProvider::class);
        $this->app->register(\Stancl\Tenancy\TenancyServiceProvider::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/telegram-bot-essentials.php', 'telegram-bot-essentials');
        $this->mergeConfigFrom(__DIR__ . '/../config/tenancy.php', 'tenancy');
        $this->mergeConfigFrom(__DIR__ . '/../config/telegram.php', 'telegram');

        $this->initializeSingletons();
    }

    private function initializeSingletons(): void
    {
        $this->app->singleton(ReplyKeyBus::class, function ($app) {
            return new ReplyKeyBus();
        });

        $this->app->singleton(CallbackQueryBus::class, function ($app) {
            return new CallbackQueryBus();
        });

        $this->app->singleton(StateAnswerBus::class, function ($app) {
            return new StateAnswerBus();
        });

        $this->app->singleton(ApiResponse::class, function ($app) {
            return new ApiResponse();
        });

        $this->app->singleton(Currency::class, function ($app) {
            return new Currency();
        });

        $this->app->singleton(Billing::class, function ($app) {
            return new Billing();
        });

        $this->initializeGatewaySingletons();
    }

    private function initializeGatewaySingletons(): void
    {
        $this->app->singleton(Gateways::class, function ($app){
            return new Gateways();
        });

        $this->app->singleton(Zibal::class, function ($app) {
            return new Zibal();
        });

        $this->app->singleton(ZarinPal::class, function ($app) {
            return new ZarinPal();
        });

        $this->app->singleton(Wallet::class, function (){
            return new Wallet();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadConsoleRoutes();
        }

        BelongsToTenant::$tenantIdColumn = 'bot_id';
        PathTenantResolver::$tenantParameterName = 'bot';

        $this->commands([
            setWebhook::class,
            BotManagementTokenCommand::class,
            MakeReplyKey::class,
            MakeCallbackQuery::class,
            MakeStateAnswer::class,
            MakeFeature::class,
            MakeCommand::class
        ]);

        Route::prefix('api')
            ->group(__DIR__ . '/../routes/api.php');
        Route::prefix('')
            ->group(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tbe');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/telegram-bot-essentials.php' => config_path('telegram-bot-essentials.php'),
        ], 'telegram-bot-essentials');

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe');
        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/telegram-bot-essentials'),
        ], 'telegram-bot-essentials-translations');
    }

    protected function loadConsoleRoutes(): void
    {
        $consoleRoutes = __DIR__ . '/../routes/console.php';

        if (file_exists($consoleRoutes)) {
            require $consoleRoutes;
        }
    }
}
