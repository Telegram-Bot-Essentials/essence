<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Web\Filament\DailySeries;

uses(RefreshDatabase::class);

it('counts per day and keeps empty days as zero', function () {
    $this->travelTo(now()->setTime(12, 0));
    TelegramUser::factory()->create(['created_at' => now()]);
    TelegramUser::factory()->create(['created_at' => now()]);
    TelegramUser::factory()->create(['created_at' => now()->subDays(2)]);
    TelegramUser::factory()->create(['created_at' => now()->subDays(40)]);

    $series = DailySeries::of(TelegramUser::query(), 'created_at', 7);

    expect($series)->toHaveCount(7)
        ->and(array_values($series))->toBe([0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 2.0])
        ->and(array_key_last($series))->toBe(now()->format('Y-m-d'))
        ->and(DailySeries::labels($series))->toHaveCount(7);
});

it('reads the offered ranges, 30 days otherwise', function () {
    expect(array_keys(DailySeries::ranges()))->toBe(['7d', '30d', '90d'])
        ->and(DailySeries::days('7d'))->toBe(7)
        ->and(DailySeries::days('nope'))->toBe(30);
});
