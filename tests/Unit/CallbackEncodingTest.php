<?php

declare(strict_types=1);

/*
 * Characterisation tests for the callback-data codec.
 *
 * These pin the behaviour that exists TODAY, bugs included, so that the
 * phase 0 fixes show up as deliberate assertion changes rather than as
 * silent regressions. Cases known to be wrong are marked BUG.
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
    // false is scalar, not null and not '', so it survives the filter and
    // strval()s to ''. Positions are preserved. This is the behaviour the
    // null/empty-string cases below should have had all along.
    expect(encodeCallback('TYPE', 'method', [7, false, 9]))->toBe('TYPE#method?7&&9');
});

it('BUG: drops a null param and shifts later params left', function () {
    // Should be 'TYPE#method?7&&9' so index 2 stays addressable.
    expect(encodeCallback('TYPE', 'method', [7, null, 9]))->toBe('TYPE#method?7&9');

    $params = decodeCallback(encodeCallback('TYPE', 'method', [7, null, 9]))['params'];
    expect($params)->toHaveCount(2)
        ->and($params[1])->toBe('9');
});

it('BUG: drops an empty-string param and shifts later params left', function () {
    expect(encodeCallback('TYPE', 'method', [7, '', 9]))->toBe('TYPE#method?7&9');
});

it('BUG: silently drops a non-scalar param', function () {
    expect(encodeCallback('TYPE', 'method', [7, ['a'], 9]))->toBe('TYPE#method?7&9');
});

it('BUG: returns a sentinel string when the result exceeds 64 bytes', function () {
    // Telegram caps callback_data at 64 bytes. Today the overflow ships a
    // button whose data is the literal sentinel, which matches no handler,
    // so the button is silently dead.
    $long = encodeCallback('TYPE', 'method', [str_repeat('x', 80)]);

    expect($long)->toBe('LONG_CALLBACK_DATA')
        ->and(decodeCallback($long)['method'])->toBeNull();
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
