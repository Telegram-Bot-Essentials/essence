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

    /** The sidebar search finds records only for who may open the list. */
    public static function canGloballySearch(): bool
    {
        return static::canAccess() && parent::canGloballySearch();
    }

    /**
     * How many records wait for someone, shown beside the sidebar item; null
     * for none. Override it for a work queue (open tickets, payments to
     * review). Counted only for who may open the list.
     */
    public static function waitingCount(): ?int
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = static::waitingCount();

        return $count !== null && $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }
}
