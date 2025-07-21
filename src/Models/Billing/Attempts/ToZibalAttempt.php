<?php

namespace Elyar\TelegramBotEssentials\Models\Billing\Attempts;

use Elyar\TelegramBotEssentials\Models\Abstract\PaymentAttempt;

class ToZibalAttempt extends PaymentAttempt
{
    protected function attemptSucceedHook(): void
    {
        // TODO: Implement attemptSucceedHook() method.
    }

    protected function attemptFailedHook(): void
    {
        // TODO: Implement attemptFailedHook() method.
    }
}
