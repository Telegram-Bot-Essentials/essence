<?php

declare(strict_types=1);

use TelegramBotEssentials\Essence\Services\ApiResponse;

it('is reachable through the tbeApiResponse helper', function () {
    expect(tbeApiResponse())->toBeInstanceOf(ApiResponse::class);
});

it('does not redefine the global apiResponse function', function () {
    // essence used to call apiResponse(), which elyar/personal-laravel-helpers
    // also defines globally. Apps that still install that package must keep
    // exactly one definition, and essence must not be the second.
    $definedHere = array_filter(
        get_defined_functions()['user'],
        fn (string $fn) => $fn === 'apiresponse'
    );

    expect(function_exists('tbeApiResponse'))->toBeTrue()
        ->and($definedHere)->toBeEmpty();
});

it('wraps a success payload', function () {
    $payload = tbeApiResponse()->success(['id' => 1]);

    expect($payload->getStatusCode())->toBe(200)
        ->and($payload->getData(true))->toBe([
            'success' => true,
            'message' => null,
            'data' => ['id' => 1],
        ]);
});

it('merges extra keys into a success payload', function () {
    $payload = tbeApiResponse()->success(['id' => 1], 201, ['meta' => ['page' => 2]]);

    expect($payload->getStatusCode())->toBe(201)
        ->and($payload->getData(true)['meta'])->toBe(['page' => 2]);
});

it('derives an error type from the status code', function () {
    expect(tbeApiResponse()->error('nope', 404)->getData(true))->toBe([
        'success' => false,
        'message' => 'nope',
        'error_type' => 'not_found',
    ]);
});

it('falls back rather than throwing on an unknown status code', function () {
    // The original helper indexed Response::$statusTexts directly and blew
    // up on a code it did not know.
    expect(tbeApiResponse()->error('nope', 599)->getData(true)['error_type'])
        ->toBe('unknown_error');
});

it('wraps a validation-style error bag', function () {
    $payload = tbeApiResponse()->errors(['name' => ['required']], 422, 'invalid');

    expect($payload->getStatusCode())->toBe(422)
        ->and($payload->getData(true)['errors'])->toBe(['name' => ['required']]);
});
