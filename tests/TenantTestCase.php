<?php

namespace Elyar\TelegramBotEssentials\Tests;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\TelegramBotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Stancl\Tenancy\TenancyServiceProvider;

class TenantTestCase extends Orchestra
{
    protected bool $tenancy = true;

    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();

        $this->initializeTenancy();
    }

    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function initializeTenancy(): void
    {
        $tenant = Bot::factory()->create()->first();
        tenancy()->initialize($tenant);
    }
//    protected function setUp(): void
//    {
//        parent::setUp();
//
//        $this->artisan('migrate')->run();
//    }
    protected function getPackageProviders($app): array
    {
        return [
            TelegramBotServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('telegram-bot-essentials.bot_access.token', 'xxx');
    }
}