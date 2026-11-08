<?php

namespace TelegramBotEssentials\Essence\Tests\Web\Fixtures;

use Illuminate\Support\ServiceProvider;

/** A companion, registering before essence's own provider has registered. */
class LateProvider extends ServiceProvider
{
    public function register(): void
    {
        webPanels()->manage(LatePlugin::class);
    }
}
