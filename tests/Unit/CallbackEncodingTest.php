<?php

declare(strict_types=1);

use TelegramBotEssentials\Essence\Exceptions\CallbackDataTooLong;
use TelegramBotEssentials\Essence\Exceptions\InvalidCallbackParam;

/*
 * Tests for the callback-data codec.
 *
 * Params are positional: N params in always means N slots out, so a
 * handler can address params[2] regardless of what params[1] held.
 */

it('encodes a type and method with no params', function () {
    expect(encodeCallback('TYPE', 'method'))->toBe('TYPE#method');
});

it('encodes params as a positional, value-only list', function () {
    expect(encodeCallback('TYPE', 'method', [7, 9]))->toBe('TYPE#method?7&9');
});

it('discards param keys, keeping only values', function () {
    expect(encodeCallback('TYPE', 'method', ['id' => 7, 'page' => 9]))
        ->toBe('TYPE#method?7&9');
});

it('round-trips through decodeCallback', function () {
    $decoded = decodeCallback(encodeCallback('TYPE', 'method', [7, 9]));

    expect($decoded['type'])->toBe('TYPE')
        ->and($decoded['method'])->toBe('method')
        ->and($decoded['params'])->toBe(['7', '9']);
});

it('decodes a bare type with no method', function () {
    expect(decodeCallback('TYPE'))->toBe([
        'type' => 'TYPE',
        'method' => null,
        'params' => [],
    ]);
});

it('decodes a method with no params', function () {
    expect(decodeCallback('TYPE#method'))->toBe([
        'type' => 'TYPE',
        'method' => 'method',
        'params' => [],
    ]);
});

it('preserves an empty positional slot on decode', function () {
    expect(decodeCallback('TYPE#method?7&&9')['params'])->toBe(['7', '', '9']);
});

it('already emits an empty slot for false, preserving arity', function () {
    // false stringifies to '', so it always occupied a slot. null and
    // the empty string now behave the same way.
    expect(encodeCallback('TYPE', 'method', [7, false, 9]))->toBe('TYPE#method?7&&9');
});

it('encodes null as an empty slot, preserving arity', function () {
    expect(encodeCallback('TYPE', 'method', [7, null, 9]))->toBe('TYPE#method?7&&9');

    $params = decodeCallback(encodeCallback('TYPE', 'method', [7, null, 9]))['params'];
    expect($params)->toHaveCount(3)
        ->and($params[1])->toBe('')
        ->and($params[2])->toBe('9');
});

it('encodes an empty string as an empty slot', function () {
    expect(encodeCallback('TYPE', 'method', [7, '', 9]))->toBe('TYPE#method?7&&9');
});

it('throws on a param it cannot stringify', function () {
    expect(fn () => encodeCallback('TYPE', 'method', [7, ['a'], 9]))
        ->toThrow(InvalidCallbackParam::class);
});

it('accepts a Stringable param', function () {
    $param = new class implements Stringable
    {
        public function __toString(): string
        {
            return '42';
        }
    };

    expect(encodeCallback('TYPE', 'method', [$param]))->toBe('TYPE#method?42');
});

it('throws when the result exceeds 64 bytes', function () {
    // Telegram caps callback_data at 64 bytes. Overflow used to return the
    // literal string LONG_CALLBACK_DATA, which matched no handler, so the
    // button rendered fine and did nothing when tapped.
    expect(fn () => encodeCallback('TYPE', 'method', [str_repeat('x', 80)]))
        ->toThrow(CallbackDataTooLong::class);
});

it('allows a result of exactly 64 bytes', function () {
    expect(strlen(encodeCallback('T', 'm', [str_repeat('x', 60)])))->toBe(64);
});

it('measures the 64-byte limit in bytes, not characters', function () {
    // 30 multi-byte chars is 60 bytes of param plus 'T#m?' = 64, at the limit.
    $params = [str_repeat('é', 30)];

    expect(strlen(encodeCallback('T', 'm', $params)))->toBe(64);
});

it('round-trips answer state through encodeAnswerState', function () {
    $encoded = encodeAnswerState('TYPE', 'method', ['id' => 7, 'page' => 9]);

    expect($encoded)->toBe('TYPE#method?id=7&page=9')
        ->and(decodeAnswerState($encoded))->toBe([
            'type' => 'TYPE',
            'method' => 'method',
            'params' => ['id' => '7', 'page' => '9'],
        ]);
});

it('encodes answer state with no params', function () {
    expect(encodeAnswerState('TYPE', 'method'))->toBe('TYPE#method');
});
