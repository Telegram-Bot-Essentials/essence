<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Support\Facades\Schema;

trait BelongsToBot
{
    public static function bootBelongsToBot(): void
    {
        static::creating(function ($model) {
            if (
                $model->isFillable('bot_id') ||
                Schema::hasColumn($model->getTable(), 'bot_id')
            ) {
                if (empty($model->bot_id)) {
                    $model->bot_id = Bot::current()->id;
                }
            }
        });

    }
}
