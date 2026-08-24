<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Support\Facades\Validator;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;

trait ValidationOnModelLevel
{
    public static function validateAttribute($attribute, $value): void
    {
        if (! property_exists(static::class, 'validationRules')) {
            throw new TbeLogicException('Validation rules not defined on '.static::class);
        }

        $rules = static::$validationRules[$attribute] ?? null;
        if (! $rules) {
            return;
        }

        Validator::validate([$attribute => $value], [$attribute => $rules]);
    }
}
