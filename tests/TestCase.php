<?php

namespace Elyar\TelegramBotEssentials\Tests;

use Elyar\TelegramBotEssentials\TelegramBotServiceProvider;
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
            \Spatie\Multitenancy\MultitenancyServiceProvider::class,
        ];
    }
}