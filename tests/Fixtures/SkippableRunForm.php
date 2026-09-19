<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\Steps\Text;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * A required step followed by a run of skippable ones, which all offer the
 * same buttons and so should share a single keyboard message.
 */
class SkippableRunForm extends Form
{
    protected string $type = 'SKIPPABLE_RUN';

    protected string $lang = 'skippable-run-form';

    public function steps(): array
    {
        return [
            Text::make('name'),
            Text::make('first')->skippable(),
            Text::make('second')->skippable(),
            Text::make('third')->skippable(),
        ];
    }

    public function onComplete(array $answers, array $ctx): TelegramResponse
    {
        return new TelegramResponse(text: 'Done');
    }
}
