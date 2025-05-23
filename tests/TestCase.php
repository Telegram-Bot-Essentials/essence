<?php

namespace Elyar\TelegramBotEssentials\Tests;

use Elyar\TelegramBotEssentials\TelegramBotServiceProvider;
use Elyar\TelegramBotEssentials\TenancyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();
    }
    protected function getPackageProviders($app): array
    {
        return [
            TelegramBotServiceProvider::class,
            TenancyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('telegram-bot-essentials.bot_access.token', 'xxx');
    }
}