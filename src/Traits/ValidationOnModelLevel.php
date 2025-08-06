<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Exceptions\TbeLogicException;
use Illuminate\Support\Facades\Validator;

trait ValidationOnModelLevel
{
    public function validateAttribute($value, $attribute): void
    {
        if (!property_exists($this, 'validationRules')) throw new TbeLogicException('Validation rules not defined');
        if (!($this->validationRules[$attribute] ?? null)) return;
        $rules = $this->validationRules[$attribute];
        Validator::validate([$attribute => $value], [$attribute => $rules]);
    }
}
