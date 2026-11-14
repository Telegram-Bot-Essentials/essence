<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use TelegramBotEssentials\Essence\Web\PanelCalendar;

/**
 * Dates in the viewer's calendar for table columns and infolist entries.
 * Filament has no Jalali calendar of its own, so these format through
 * PanelCalendar instead of dateTime().
 */
final class PanelDates
{
    /**
     * "3 days ago", with the exact date in the viewer's calendar on hover.
     *
     * @template T of TextColumn|TextEntry
     *
     * @param  T  $component
     * @return T
     */
    public static function since(TextColumn|TextEntry $component): TextColumn|TextEntry
    {
        return $component->since()->tooltip(fn (mixed $state): ?string => $state === null ? null : PanelCalendar::format($state));
    }

    /**
     * @template T of TextColumn|TextEntry
     *
     * @param  T  $component
     * @return T
     */
    public static function dateTime(TextColumn|TextEntry $component): TextColumn|TextEntry
    {
        return $component->formatStateUsing(fn (mixed $state): string => PanelCalendar::format($state));
    }

    /**
     * @template T of TextColumn|TextEntry
     *
     * @param  T  $component
     * @return T
     */
    public static function date(TextColumn|TextEntry $component): TextColumn|TextEntry
    {
        return $component->formatStateUsing(fn (mixed $state): string => PanelCalendar::format($state, withTime: false));
    }
}
