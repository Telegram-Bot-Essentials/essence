<?php

namespace Elyar\TelegramBotEssentials\Tests;

use Elyar\TelegramBotEssentials\TelegramBotServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Multitenancy\MultitenancyServiceProvider;

class TestCase extends Orchestra
{
    use WithWorkbench;
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();
    }
    protected function getPackageProviders($app): array
    {
        return [
            TelegramBotServiceProvider::class,
            MultitenancyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('telegram-bot-essentials.bot_access.token', 'xxx');
    }
}