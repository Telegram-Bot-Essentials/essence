<?php

declare(strict_types=1);

/*
 * Tests for nextInArray / prevInArray.
 *
 * Both walk a list of values and wrap around the ends, so a "cycle to the
 * next option" button never runs off the array. Keys are ignored; a value
 * that is not present yields null.
 */

it('returns the following element', function () {
    expect(nextInArray([10, 20, 30], 20))->toBe(30);
});

it('wraps past the last element to the first', function () {
    expect(nextInArray([10, 20, 30], 30))->toBe(10);
});

it('returns the preceding element', function () {
    expect(prevInArray([10, 20, 30], 20))->toBe(10);
});

it('wraps past the first element to the last', function () {
    expect(prevInArray([10, 20, 30], 10))->toBe(30);
});

it('treats a null current as "before the start"', function () {
    expect(nextInArray([10, 20, 30], null))->toBe(10)
        ->and(prevInArray([10, 20, 30], null))->toBe(30);
});

it('returns null when the current value is not in the list', function () {
    expect(nextInArray([10, 20, 30], 99))->toBeNull()
        ->and(prevInArray([10, 20, 30], 99))->toBeNull();
});

it('returns null for an empty list', function () {
    expect(nextInArray([], 1))->toBeNull()
        ->and(nextInArray([], null))->toBeNull()
        ->and(prevInArray([], null))->toBeNull();
});

it('ignores keys and compares by value', function () {
    expect(nextInArray(['a' => 'admin', 'b' => 'support', 'c' => 'owner'], 'support'))
        ->toBe('owner');
});

it('matches loosely, like array_search', function () {
    expect(nextInArray(['0', '1', '2'], 1))->toBe('2');
});
