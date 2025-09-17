<?php

namespace TelegramBotEssentials\Essence\Traits;

trait PriceBasedOnCurrency
{
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->currency)) {
                $model->currency = $model->bot->currency;
            }
        });
    }
}
