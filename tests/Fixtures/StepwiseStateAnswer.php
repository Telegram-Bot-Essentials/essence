<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

/**
 * A StateAnswer whose keyboard and accepted input depend on the step named
 * in its params, the way a multi-step flow needs them to.
 */
class StepwiseStateAnswer extends StateAnswer
{
    public static array $handled = [];

    protected string $type = 'STEPWISE';

    protected int $perm = 0;

    public function getAllowedFields(): array
    {
        return ($this->params['step'] ?? null) === 'photo'
            ? [AllowableFields::PHOTO->value]
            : [AllowableFields::TEXT->value];
    }

    public function keyboard(): ?Keyboard
    {
        return match ($this->params['step'] ?? null) {
            'code' => Keyboard::make()->setResizeKeyboard(true)->row(['Skip', 'Cancel']),
            default => null,
        };
    }

    public function answer(string $step): void
    {
        self::$handled[] = $step;
    }
}
