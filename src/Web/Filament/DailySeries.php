<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A count or a sum per day over the last days, with the days that had
 * nothing kept as zero, for a chart or a stat's sparkline. DATE() reads the
 * same on MySQL and SQLite.
 */
final class DailySeries
{
    /**
     * @param  Builder<covariant Model>  $query  already narrowed (status, type...)
     * @param  literal-string  $dateColumn  a column of the query's table, from code
     * @param  literal-string|null  $sum  the column to add up, from code; null counts rows
     * @return array<string, float> value per day, oldest first, keyed Y-m-d
     */
    public static function of(Builder $query, string $dateColumn, int $days = 30, ?string $sum = null): array
    {
        $from = CarbonImmutable::now()->startOfDay()->subDays($days - 1);
        $model = $query->getModel();

        $rows = (clone $query)->toBase()
            ->where($model->qualifyColumn($dateColumn), '>=', $from)
            ->selectRaw('DATE('.$dateColumn.') as day')
            ->selectRaw($sum === null ? 'COUNT(*) as total' : 'COALESCE(SUM('.$sum.'), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->addDays($i)->format('Y-m-d');
            $value = $rows[$day] ?? 0;
            $series[$day] = is_numeric($value) ? (float) $value : 0.0;
        }

        return $series;
    }

    /**
     * Short day labels for a chart axis, in the panel's language.
     *
     * @param  array<string, float>  $series
     * @return list<string>
     */
    public static function labels(array $series): array
    {
        return array_map(
            fn (string $day): string => CarbonImmutable::parse($day)->translatedFormat('M j'),
            array_keys($series),
        );
    }

    /**
     * The ranges every chart offers, for a ChartWidget's getFilters().
     *
     * @return array<string, string>
     */
    public static function ranges(): array
    {
        return [
            '7d' => (string) __('tbe-essence::web.charts.days', ['days' => 7]),
            '30d' => (string) __('tbe-essence::web.charts.days', ['days' => 30]),
            '90d' => (string) __('tbe-essence::web.charts.days', ['days' => 90]),
        ];
    }

    /** The days in a range from ranges(), 30 for anything else. */
    public static function days(?string $range): int
    {
        return match ($range) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
    }
}
