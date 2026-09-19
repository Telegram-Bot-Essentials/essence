<?php

namespace TelegramBotEssentials\Essence\Forms\Steps;

/**
 * A free-text answer typed by the user.
 */
class Text extends Step
{
    protected function effectiveRules(array $answers): array
    {
        $rules = parent::effectiveRules($answers);

        $rules[] = 'string';

        if (! $this->declaresSize($rules)) {
            $max = config('tbe-essence.forms.max_text_length', 1000);
            $rules[] = 'max:'.(is_numeric($max) ? (int) $max : 1000);
        }

        return $rules;
    }

    /** @param  array<mixed>  $rules */
    private function declaresSize(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && preg_match('/^(max|size|between)(:|$)/', $rule)) {
                return true;
            }
        }

        return false;
    }
}
