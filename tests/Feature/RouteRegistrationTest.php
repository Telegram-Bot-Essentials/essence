<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

afterEach(function () {
    putenv('TBE_ROUTES_API_PREFIX');
    putenv('TBE_ROUTES_ENABLED');
    unset($_ENV['TBE_ROUTES_API_PREFIX'], $_ENV['TBE_ROUTES_ENABLED']);
});

it('registers the package routes under the default prefix', function () {
    expect(Route::has('index'))->toBeTrue();

    $uri = collect(Route::getRoutes())
        ->first(fn ($route) => $route->getName() === 'index')
        ->uri();

    expect($uri)->toStartWith('api/');
});

it('mounts the package routes under a configured prefix', function () {
    putenv('TBE_ROUTES_API_PREFIX=tg');
    $_ENV['TBE_ROUTES_API_PREFIX'] = 'tg';

    $this->refreshApplication();

    $uri = collect(Route::getRoutes())
        ->first(fn ($route) => $route->getName() === 'index')
        ->uri();

    expect($uri)->toStartWith('tg/');
});

it('does not register the package routes when disabled', function () {
    putenv('TBE_ROUTES_ENABLED=false');
    $_ENV['TBE_ROUTES_ENABLED'] = 'false';

    $this->refreshApplication();

    expect(Route::has('index'))->toBeFalse();
});
