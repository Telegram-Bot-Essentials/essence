<?php

namespace TelegramBotEssentials\Essence\Web\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsWidget;
use TelegramBotEssentials\Essence\Web\Filament\DailySeries;

/** Who started the bot each day: the dashboard's first chart, with no package installed. */
class NewMembersChart extends ChartWidget
{
    use GuardsWidget;

    protected static ?string $ability = null;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30d';

    public function getHeading(): string
    {
        return (string) __('tbe-essence::web.charts.new_members');
    }

    /** @return array<string, string> */
    protected function getFilters(): array
    {
        return DailySeries::ranges();
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $series = DailySeries::of(BotUser::query(), 'created_at', DailySeries::days($this->filter));

        return [
            'datasets' => [[
                'label' => (string) __('tbe-essence::web.charts.new_members'),
                'data' => array_values($series),
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => DailySeries::labels($series),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
