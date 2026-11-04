<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Pages\Page;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsPanelAccess;

/**
 * Base for a package's panel page. Names its ability like a handler does, e.g.
 * `protected static ?string $ability = 'support.manage';`. Handler gates apply,
 * and a page opts in or out with the same marker interfaces a handler uses.
 */
abstract class PanelPage extends Page
{
    use GuardsPanelAccess;

    protected static ?string $ability = null;

    public static function requiredAbility(): ?string
    {
        return static::$ability;
    }
}
