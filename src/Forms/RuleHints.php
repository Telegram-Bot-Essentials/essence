<?php

namespace TelegramBotEssentials\Essence\Forms;

/**
 * Turns a step's Laravel validation rules into the "what is expected" part
 * of its prompt, e.g. `a number between 1 and 100`. Only string rules can be
 * read; closure and object rules are skipped, and a step whose rules cannot
 * be described sets its own hint().
 */
class RuleHints
{
    /**
     * @param  array<mixed>  $rules
     */
    public static function describe(array $rules): string
    {
        $named = [];

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            [$name, $argument] = array_pad(explode(':', $rule, 2), 2, '');
            $named[$name] = $argument === '' ? [] : explode(',', $argument);
        }

        $numeric = isset($named['numeric']) || isset($named['integer']) || isset($named['decimal']);
        $parts = [];

        if (isset($named['integer'])) {
            $parts[] = __('tbe::forms.hints.integer');
        } elseif (isset($named['numeric']) || isset($named['decimal'])) {
            $parts[] = __('tbe::forms.hints.numeric');
        }

        $unit = $numeric ? 'number' : 'length';

        if (isset($named['between'][1])) {
            $parts[] = __("tbe::forms.hints.between_{$unit}", ['min' => $named['between'][0], 'max' => $named['between'][1]]);
        } else {
            if (isset($named['min'][0])) {
                $parts[] = __("tbe::forms.hints.min_{$unit}", ['min' => $named['min'][0]]);
            }
            if (isset($named['max'][0])) {
                $parts[] = __("tbe::forms.hints.max_{$unit}", ['max' => $named['max'][0]]);
            }
        }

        if (isset($named['date'])) {
            $parts[] = __('tbe::forms.hints.date', ['format' => 'Y-m-d']);
        }
        if (isset($named['date_format'][0])) {
            $parts[] = __('tbe::forms.hints.date', ['format' => $named['date_format'][0]]);
        }
        if (isset($named['email'])) {
            $parts[] = __('tbe::forms.hints.email');
        }
        if (isset($named['url'])) {
            $parts[] = __('tbe::forms.hints.url');
        }
        if (isset($named['in'])) {
            $parts[] = __('tbe::forms.hints.in', ['values' => implode(', ', $named['in'])]);
        }

        return implode(', ', $parts);
    }
}
