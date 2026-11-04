<?php

namespace TelegramBotEssentials\Essence\Tests\Web\Fixtures;

use TelegramBotEssentials\Essence\Contracts\HandlerGate;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;

class RefusingGate implements HandlerGate
{
    public function check(object $handler): void
    {
        throw new FeatureIsDisabled('Refused');
    }
}
