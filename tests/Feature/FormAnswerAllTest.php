<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Essence\Tests\Fixtures\SampleForm;

/*
 * A form answered all at once, as the web panel sends it: the chat's rules,
 * conditions and casts apply, without a conversation.
 */

function answerSample(array $raw): array
{
    return formEngine()->answerAll(new SampleForm, $raw);
}

it('casts the answers of the steps that apply', function () {
    expect(answerSample(['code' => ' SUMMER ', 'type' => 'fixed', 'amount' => '500', 'max_discount' => '9', 'category' => 'c3']))
        ->toBe(['code' => 'SUMMER', 'type' => 'fixed', 'amount' => 500.0, 'category' => 'c3']);
});

it('applies a conditional step and lets a skippable one stay empty', function () {
    expect(answerSample(['code' => 'A', 'type' => 'percentage', 'amount' => '20', 'max_discount' => '', 'category' => 'c1']))
        ->toBe(['code' => 'A', 'type' => 'percentage', 'amount' => 20.0, 'max_discount' => null, 'category' => 'c1']);
});

it('checks rules that depend on earlier answers', function () {
    answerSample(['code' => 'A', 'type' => 'percentage', 'amount' => '150', 'category' => 'c1']);
})->throws(ValidationException::class);

it('refuses a missing required answer, keyed by its step', function () {
    try {
        answerSample(['code' => 'A', 'type' => 'fixed', 'category' => 'c1']);
        $this->fail('No exception');
    } catch (ValidationException $e) {
        expect(array_keys($e->errors()))->toBe(['amount']);
    }
});

it('refuses an option that is not offered', function () {
    answerSample(['code' => 'A', 'type' => 'free', 'amount' => '1', 'category' => 'c1']);
})->throws(ValidationException::class);

it('tells whether a step applies given the other answers', function () {
    $form = new SampleForm;
    $step = formEngine()->steps($form)['max_discount'];

    expect(formEngine()->contextOf($form, $step, ['type' => 'percentage'])[0])->toBeTrue()
        ->and(formEngine()->contextOf($form, $step, ['type' => 'fixed'])[0])->toBeFalse();
});
