<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\RuleHints;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Text;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Essence\Tests\Fixtures\SampleForm;

uses(RefreshDatabase::class);

it('describes common rules as the expected value', function (array $rules, string $expected) {
    expect(RuleHints::describe($rules))->toBe($expected);
})->with([
    'integer range' => [['integer', 'between:1,100'], 'a whole number, between 1 and 100'],
    'number with a floor' => [['numeric', 'min:0.5'], 'a number, at least 0.5'],
    'text length' => [['max:32'], 'up to 32 characters'],
    'date' => [['date'], 'a date (Y-m-d)'],
    'one of' => [['in:a,b'], 'one of: a, b'],
    'closures cannot be read' => [[fn () => true], ''],
]);

it('marks a step Required or Optional and appends what the rules expect', function () {
    expect(Text::make('amount')->rules(['numeric', 'min:1'])->hintLine([]))->toBe('Required · a number, at least 1')
        ->and(Text::make('note')->skippable()->hintLine([]))->toBe('Optional')
        ->and(Text::make('x')->rules([fn () => true])->hint('anything goes')->hintLine([]))->toBe('Required · anything goes');
});

it('caps free text at the configured length unless the step sets its own size', function () {
    config(['tbe-essence.forms.max_text_length' => 20]);

    expect(fn () => Text::make('note')->validate(str_repeat('a', 21), [], 'Note'))->toThrow(ValidationException::class);
    Text::make('note')->validate(str_repeat('a', 20), [], 'Note');
    Text::make('note')->rules(['max:50'])->validate(str_repeat('a', 30), [], 'Note');
});

it('words a failed rule for the user, naming the step', function () {
    try {
        Text::make('amount')->rules(['numeric'])->validate('abc', [], 'Amount');
    } catch (ValidationException $e) {
        expect($e->getMessage())->toContain('Amount');

        return;
    }

    $this->fail('Expected a ValidationException.');
});

it('gives a rules closure the answers given so far', function () {
    $step = Text::make('cap')->rules(fn (array $answers) => $answers['type'] === 'percentage' ? ['max:3'] : []);

    expect(fn () => $step->validate('1000', ['type' => 'percentage'], 'Cap'))->toThrow(ValidationException::class);
    $step->validate('1000', ['type' => 'fixed'], 'Cap');
});

it('validates a choice against its options and shows the label of a picked one', function () {
    $choice = Choice::make('type')->options(['percentage' => 'Percentage']);

    $choice->validate('percentage', [], 'Type');
    expect(fn () => $choice->validate('other', [], 'Type'))->toThrow(ValidationException::class)
        ->and($choice->displayAnswer('percentage'))->toBe('Percentage')
        ->and($choice->valueForLabel('Percentage'))->toBe('percentage')
        ->and($choice->valueForLabel('nope'))->toBeNull();
});

it('shows fixed options on the reply keyboard and closure options inline', function () {
    expect(Choice::make('a')->options(['x' => 'X'])->isInline())->toBeFalse()
        ->and(Choice::make('a')->options(fn () => ['x' => 'X'])->isInline())->toBeTrue()
        ->and(Choice::make('a')->options(['x' => 'X'])->inline()->isInline())->toBeTrue();
});

it('shapes an answer for completion only when a cast is set, leaving skipped answers null', function () {
    $step = Text::make('n')->cast(fn (string $value) => (int) $value);

    expect($step->castAnswer('5', []))->toBe(5)
        ->and($step->castAnswer(null, []))->toBeNull()
        ->and(Text::make('n')->castAnswer('5', []))->toBe('5');
});

it('refuses a form that repeats a step key', function () {
    $form = new class extends Form
    {
        protected string $type = 'DUPLICATE';

        public function steps(): array
        {
            return [Text::make('a'), Text::make('a')];
        }

        public function onComplete(array $answers, array $ctx): TelegramResponse
        {
            return new TelegramResponse(text: '');
        }
    };

    expect(fn () => formEngine()->steps($form))->toThrow(LogicException::class);
});

it('registers a form by type key and by class', function () {
    $form = formRegistry()->addForm(SampleForm::class);

    expect(formRegistry()->get('SAMPLE'))->toBe($form)
        ->and(formRegistry()->get(SampleForm::class))->toBe($form)
        ->and(formRegistry()->get('MISSING'))->toBeNull();
});
