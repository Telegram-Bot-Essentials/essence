<?php

namespace Elyar\TelegramBotEssentials;

use Elyar\TelegramBotEssentials\Console\Commands\MakeCallbackQuery;
use Elyar\TelegramBotEssentials\Console\Commands\MakeReplyKey;
use Elyar\TelegramBotEssentials\Console\Commands\MakeStateAnswer;
use Elyar\TelegramBotEssentials\Console\Commands\setWebhook;
use Elyar\TelegramBotEssentials\Console\Commands\BotManagementTokenCommand;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQueryBus;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKeyBus;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswerBus;
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

    public function boot(): void
    {
        $this->commands([
            MakeReplyKey::class,
            setWebhook::class,
            BotManagementTokenCommand::class,
            MakeCallbackQuery::class,
            MakeStateAnswer::class
        ]);

        Route::prefix('api/')
            ->group(__DIR__ . '/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/telegram-bot-essentials.php' => config_path('telegram-bot-essentials.php'),
        ], 'telegram-bot-essentials');

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe');
        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/telegram-bot-essentials'),
        ], 'telegram-bot-essentials-translations');
    }
}
