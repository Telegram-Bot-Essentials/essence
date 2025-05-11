<?php

namespace Elyar\TelegramBotEssentials;

use Elyar\TelegramBotEssentials\Console\Commands\MakeReplyKey;
use Elyar\TelegramBotEssentials\Console\Commands\setWebhook;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQueryBus;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKeyBus;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswerBus;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
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
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function boot(): void
    {
        $this->commands([
            MakeReplyKey::class,
            setWebhook::class,
        ]);

        Route::prefix('api/')
            ->group(__DIR__ . '/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/telegram-bot-essentials.php' => config_path('telegram-bot-essentials.php'),
        ], 'telegram-bot-essentials');

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'telegram-bot-essentials');
        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/telegram-bot-essentials'),
        ], 'telegram-bot-essentials-translations');

        $this->publishes([
            __DIR__.'/../../database/seeders/Development/BotSeeder.php' => database_path('seeders/Development/BotSeeder.php'),
            __DIR__.'/../../database/seeders/Development/TelegramUserSeeder.php' => database_path('seeders/Development/TelegramUserSeeder.php'),
        ], 'seeders');

        $adminQueries = base_path('app/Telegram/CallbackQueries/Admin');
        $memberQueries = base_path('app/Telegram/CallbackQueries/Member');
        $adminStateAnswers = base_path('app/Telegram/StateAnswers/Admin');
        $memberStateAnswers = base_path('app/Telegram/StateAnswers/Member');
//        $adminReplyKeys = base_path('app/Telegram/ReplyKeys/Admin');
//        $memberReplyKeys = base_path('app/Telegram/ReplyKeys/Member');
        if (is_dir($adminQueries)) $this->autoLoadCallbackQueries($adminQueries);
        if (is_dir($memberQueries)) $this->autoLoadCallbackQueries($memberQueries);
        $this->autoLoadCallbackQueries(__DIR__ . '/Telegram/CallbackQueries/Member');
        $this->autoLoadCallbackQueries(__DIR__ . '/Telegram/CallbackQueries/Admin');

        if (is_dir($adminStateAnswers)) $this->autoLoadStateAnswers($adminStateAnswers);
        if (is_dir($memberStateAnswers)) $this->autoLoadStateAnswers($memberStateAnswers);
        $this->autoLoadStateAnswers(__DIR__ . '/Telegram/StateAnswers/Member');
        $this->autoLoadStateAnswers(__DIR__ . '/Telegram/StateAnswers/Admin');

        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.admin') ?? []);
        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.member') ?? []);
        $this->autoLoadReplyKeys(__DIR__ . '/Telegram/ReplyKeys/Member');
        $this->autoLoadReplyKeys(__DIR__ . '/Telegram/ReplyKeys/Admin');
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
                    throw new LogicException("ReplyKey {$replyKey} is not a subclass of Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey");
                replyKeyBus()->addReplyKey($replyKey);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadCallbackQueries(string $path): void
    {
        $files = File::allFiles($path);

        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__);
            $baseNamespace = 'Elyar\\TelegramBotEssentials';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace('/', '\\', $relativePath);
        $fullNamespace = rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace;

        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();
            $fqcn = $fullNamespace . '\\' . $className;

            if (class_exists($fqcn) && is_subclass_of($fqcn, CallbackQuery::class)) {
                callbackQueryBus()->addCallbackQuery($fqcn);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     */
    private function autoLoadStateAnswers(string $path): void
    {
        $files = File::allFiles($path);

        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__);
            $baseNamespace = 'Elyar\\TelegramBotEssentials';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace('/', '\\', $relativePath);
        $fullNamespace = rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace;

        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();
            $fqcn = $fullNamespace . '\\' . $className;

            if (class_exists($fqcn) && is_subclass_of($fqcn, StateAnswer::class)) {
                stateAnswerBus()->addStateAnswer($fqcn);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadReplyKeys(string $path): void
    {
        $files = File::allFiles($path);

        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__);
            $baseNamespace = 'Elyar\\TelegramBotEssentials';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace('/', '\\', $relativePath);
        $fullNamespace = rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace;

        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();
            $fqcn = $fullNamespace . '\\' . $className;

            if (class_exists($fqcn) && is_subclass_of($fqcn, ReplyKey::class)) {
                replyKeyBus()->addReplyKey($fqcn);
            }
        }
    }
}
