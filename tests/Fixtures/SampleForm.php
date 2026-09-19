<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Text;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * An offer-shaped form: a plain text step, a static choice, an amount whose
 * rules and meaning depend on that choice, a conditional skippable step, and
 * a dynamic (inline) choice long enough to page.
 */
class SampleForm extends Form
{
    /** @var array{0: array<string, mixed>, 1: array<string, mixed>}|null */
    public static ?array $completed = null;

    /** @var list<array<string, mixed>> */
    public static array $cancelled = [];

    protected string $type = 'SAMPLE';

    protected string $lang = 'sample-form';

    public function steps(): array
    {
        return [
            Text::make('code')->rules(['max:10'])->placeholder('SUMMER10'),
            Choice::make('type')->options(['percentage' => 'Percentage', 'fixed' => 'Fixed amount']),
            Text::make('amount')
                ->dependsOn('type')
                ->rules(fn (array $answers) => ($answers['type'] ?? null) === 'percentage' ? ['numeric', 'between:1,100'] : ['numeric', 'min:1'])
                ->cast(fn (string $value) => (float) $value),
            Text::make('max_discount')
                ->skippable()
                ->rules(['numeric'])
                ->when(fn (array $answers) => ($answers['type'] ?? null) === 'percentage'),
            Choice::make('category')->options(fn () => collect(range(1, 12))->mapWithKeys(fn (int $i) => ["c{$i}" => "Category {$i}"])->all()),
        ];
    }

    public function onComplete(array $answers, array $ctx): TelegramResponse
    {
        self::$completed = [$answers, $ctx];

        return new TelegramResponse(text: 'Created');
    }

    public function originalScreen(array $ctx): ?TelegramResponse
    {
        return new TelegramResponse(text: 'Refreshed list');
    }

    public function onCancel(array $ctx): void
    {
        self::$cancelled[] = $ctx;
    }
}
