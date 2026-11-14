<?php

namespace TelegramBotEssentials\Essence\Tests\Web\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;

class LatePlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'late';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([LatePage::class]);
    }

    public function boot(Panel $panel): void {}
}
