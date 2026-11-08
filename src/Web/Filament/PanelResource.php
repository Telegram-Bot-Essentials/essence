<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Base for a package's panel resource. Its pages use GuardsResourcePage, which
 * reads the ability from here.
 */
abstract class PanelResource extends Resource
{
    protected static ?string $ability = null;

    public static function requiredAbility(): ?string
    {
        return static::$ability;
    }

    public static function canAccess(): bool
    {
        $ability = static::requiredAbility();
        $user = PanelActor::current();

        return $ability === null || ($user instanceof BotUser && Gate::forUser($user)->allows($ability));
    }
}
