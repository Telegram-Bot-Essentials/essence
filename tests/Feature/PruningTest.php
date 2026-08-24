<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\StateData;

uses(RefreshDatabase::class);

it('prunes state data older than the configured retention window, keeping recent rows', function () {
    $old = StateData::create(['data' => ['x' => 1]]);
    $old->forceFill(['created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)])->save();

    $recent = StateData::create(['data' => ['x' => 2]]);

    $old->prunable()->get()->each->prune();

    expect(StateData::find($old->id))->toBeNull()
        ->and(StateData::find($recent->id))->not->toBeNull();
});

it('prunes inline confirmations older than the configured retention window, keeping recent rows', function () {
    $old = InlineConfirmation::create(['callback_data' => 'a', 'back_callback_data' => 'b']);
    $old->forceFill(['updated_at' => now()->subHours(7)])->save();

    $recent = InlineConfirmation::create(['callback_data' => 'c', 'back_callback_data' => 'd']);

    $old->prunable()->get()->each->prune();

    expect(InlineConfirmation::find($old->id))->toBeNull()
        ->and(InlineConfirmation::find($recent->id))->not->toBeNull();
});

it('prunes message metas older than the configured retention window, keeping recent rows', function () {
    $old = MessageMeta::create(['chat_id' => 1, 'message_id' => 1]);
    $old->forceFill(['updated_at' => now()->subDays(8)])->save();

    $recent = MessageMeta::create(['chat_id' => 2, 'message_id' => 2]);

    $old->prunable()->get()->each->prune();

    expect(MessageMeta::find($old->id))->toBeNull()
        ->and(MessageMeta::find($recent->id))->not->toBeNull();
});
