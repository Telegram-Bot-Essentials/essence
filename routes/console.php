<?php

use Illuminate\Support\Facades\Schedule;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\StateData;

Schedule::command('model:prune', [
    '--model' => [
        StateData::class,
        InlineConfirmation::class,
        MessageMeta::class,
    ],
])->hourly();
