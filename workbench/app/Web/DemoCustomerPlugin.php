<?php

namespace Workbench\App\Web;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Workbench\App\Web\Pages\BuyService;
use Workbench\App\Web\Pages\CustomerTicket;

class DemoCustomerPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'demo-customer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([BuyService::class, CustomerTicket::class]);
    }

    public function boot(Panel $panel): void {}
}
