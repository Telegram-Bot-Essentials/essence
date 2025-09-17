<?php

namespace TelegramBotEssentials\Essence;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
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
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Services\Billing;
use TelegramBotEssentials\Essence\Services\Currency;
use TelegramBotEssentials\Essence\Services\Gateways\Gateways;
use TelegramBotEssentials\Essence\Services\Gateways\Wallet;
use TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\ZarinPal;
use TelegramBotEssentials\Essence\Services\Gateways\Zibal\Zibal;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;
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
        $this->loadConsoleRoutes();

        $this->registerCommands();
        $this->registerPublishing();

        BelongsToTenant::$tenantIdColumn = 'bot_id';
        PathTenantResolver::$tenantParameterName = 'bot';

        Route::prefix('api')
            ->group(__DIR__ . '/../routes/api.php');
        Route::prefix('')
            ->group(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tbe');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-essence');

        $this->initializeOptions();
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
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tbe-essence.php' => config_path('tbe-essence.php'),
            ], 'tbe-essence-config');

            $this->publishes([
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-essence'),
            ], 'tbe-essence-translations');
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
     * @throws LogicException
     * @throws BindingResolutionException
     */
    private function initializeOptions(): void
    {
        $adminQueries = base_path('app/Telegram/CallbackQueries/Admin');
        $memberQueries = base_path('app/Telegram/CallbackQueries/Member');
        $adminStateAnswers = base_path('app/Telegram/StateAnswers/Admin');
        $memberStateAnswers = base_path('app/Telegram/StateAnswers/Member');
        if (is_dir($adminQueries)) $this->autoLoadCallbackQueries($adminQueries);
        if (is_dir($memberQueries)) $this->autoLoadCallbackQueries($memberQueries);
        $this->autoLoadCallbackQueries(realpath(__DIR__ . '/Telegram/CallbackQueries/Member'));
        $this->autoLoadCallbackQueries(realpath(__DIR__ . '/Telegram/CallbackQueries/Admin'));

        if (is_dir($adminStateAnswers)) $this->autoLoadStateAnswers($adminStateAnswers);
        if (is_dir($memberStateAnswers)) $this->autoLoadStateAnswers($memberStateAnswers);
        $this->autoLoadStateAnswers(realpath(__DIR__ . '/Telegram/StateAnswers/Member'));
        $this->autoLoadStateAnswers(realpath(__DIR__ . '/Telegram/StateAnswers/Admin'));

        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.admin') ?? []);
        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.member') ?? []);
        $this->autoLoadReplyKeys(realpath(__DIR__ . '/Telegram/ReplyKeys/Member'));
        $this->autoLoadReplyKeys(realpath(__DIR__ . '/Telegram/ReplyKeys/Admin'));
    }

    /**
     * @param string $path
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadCallbackQueries(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, CallbackQuery::class)) {
                callbackQueryBus()->addCallbackQuery($fqcn);
            }
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolveNamespace(string $path): string
    {
        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__ . '/../../');
            $baseNamespace = 'TelegramBotEssentials\\Essence';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace('/', '\\', $relativePath);

        return trim(rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace, '\\');
    }

    /**
     * @param string $path
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadStateAnswers(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, StateAnswer::class)) {
                stateAnswerBus()->addStateAnswer($fqcn);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function addUserReplyKeys(array $replyKeys): void
    {
        foreach ($replyKeys as $replyKeyRow) {
            foreach ($replyKeyRow as $replyKey) {
                if (!is_subclass_of($replyKey, ReplyKey::class))
                    throw new LogicException("ReplyKey {$replyKey} is not a subclass of TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey");
                replyKeyBus()->addReplyKey($replyKey);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadReplyKeys(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, ReplyKey::class)) {
                replyKeyBus()->addReplyKey($fqcn);
            }
        }
    }
}
