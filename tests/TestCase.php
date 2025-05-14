<?php

namespace Elyar\TelegramBotEssentials\Tests;

use Elyar\TelegramBotEssentials\TelegramBotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TelegramBotServiceProvider::class,
        ];
    }
}