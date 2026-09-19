<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Text;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * A form whose second step's rules depend on the first answer without
 * declaring dependsOn(), so only the engine's re-validation can catch a cap
 * that stops being valid.
 */
class SafetyNetForm extends Form
{
    protected string $type = 'SAFETY_NET';

    protected string $lang = 'safety-net-form';

    public function steps(): array
    {
        return [
            Choice::make('type')->options(['percentage' => 'Percentage', 'fixed' => 'Fixed amount']),
            Text::make('cap')->rules(fn (array $answers) => ($answers['type'] ?? null) === 'percentage' ? ['numeric', 'max:100'] : ['numeric', 'min:1']),
        ];
    }

    public function onComplete(array $answers, array $ctx): TelegramResponse
    {
        return new TelegramResponse(text: 'Created');
    }
}
