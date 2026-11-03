<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Contracts\RecipientGroups;
use TelegramBotEssentials\Essence\Support\NoRecipientGroups;

uses(RefreshDatabase::class);

it('has no groups until a package brings some', function () {
    $bot = $this->makeBot();
    $groups = app(RecipientGroups::class);

    expect($groups)->toBeInstanceOf(NoRecipientGroups::class)
        ->and($groups->options($bot))->toBe([])
        ->and($groups->members($bot, ['1'])->all())->toBe([]);
});
