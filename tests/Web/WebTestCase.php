<?php

namespace TelegramBotEssentials\Essence\Tests\Web;

use TelegramBotEssentials\Essence\Testing\WebTestCase as BaseWebTestCase;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class WebTestCase extends BaseWebTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            Fixtures\LateProvider::class,
            ...parent::getPackageProviders($app),
            WorkbenchServiceProvider::class,
        ];
    }
}
