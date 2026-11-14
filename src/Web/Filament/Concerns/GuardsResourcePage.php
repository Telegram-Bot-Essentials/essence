<?php

namespace TelegramBotEssentials\Essence\Web\Filament\Concerns;

use TelegramBotEssentials\Essence\Web\Filament\PanelResource;

/**
 * GuardsPanelAccess for a resource's pages: the ability lives on the resource.
 *
 * @phpstan-ignore trait.unused (used by the packages, not by essence)
 */
trait GuardsResourcePage
{
    use GuardsPanelAccess;

    public static function requiredAbility(): ?string
    {
        $resource = static::getResource();

        return is_subclass_of($resource, PanelResource::class) ? $resource::requiredAbility() : null;
    }
}
