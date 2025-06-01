<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;

class ToZirgozarAttempt extends PaymentAttempt
{
    //
    public function attemptSucceedHook(): void
    {
        // TODO: Implement attemptSucceedHook() method.
    }

    public function attemptFailedHook(): void
    {
        // TODO: Implement attemptFailedHook() method.
    }
}
