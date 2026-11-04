<?php

namespace Workbench\App\Web;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Workbench\App\Web\Pages\AdminTicket;

class DemoAdminPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'demo-admin';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([AdminTicket::class])->resources([BotUserResource::class]);
    }

    public function boot(Panel $panel): void {}
}
