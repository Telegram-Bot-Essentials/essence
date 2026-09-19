<?php

return [
    'buttons' => [
        'back' => '⬅️ Back',
        'next' => 'Next ➡️',
        'skip' => '⏭ Skip',
        'clear' => '🧹 Clear',
        'confirm' => '✅ Confirm',
    ],

    'marker' => [
        'required' => 'Required',
        'optional' => 'Optional',
    ],

    'hints' => [
        'integer' => 'a whole number',
        'numeric' => 'a number',
        'min_number' => 'at least :min',
        'max_number' => 'at most :max',
        'between_number' => 'between :min and :max',
        'min_length' => 'at least :min characters',
        'max_length' => 'up to :max characters',
        'between_length' => ':min to :max characters',
        'date' => 'a date (:format)',
        'email' => 'an email address',
        'url' => 'a URL',
        'in' => 'one of: :values',
    ],

    'prompt' => [
        'current' => 'Current: :value',
        'answer' => '➜ :value',
        'skipped' => '⏭ Skipped',
        'cancelled' => '✖ Cancelled',
        'revised' => '↩ revised',
        'none' => '—',
        'pickOption' => '⬇ Choose one',
        'picked' => '✔',
        'keyboard' => '⬇',
    ],

    'summary' => [
        'title' => 'Please review',
    ],

    'notice' => [
        'reset' => '⚠ :cleared reset because :changed changed.',
    ],

    'locked' => 'In progress…',
    'finished' => '✅ Done.',
    'expired' => '⌛ This form expired after being left idle, so it was cancelled.',

    'errors' => [
        'invalidChoice' => '❗️ Please pick one of the offered options.',
        'useButtons' => '❗️ Please use the buttons below.',
        'outdated' => 'That button is outdated.',
        'incomplete' => '❗️ Some answers are missing or no longer valid, please review them.',
    ],
];
