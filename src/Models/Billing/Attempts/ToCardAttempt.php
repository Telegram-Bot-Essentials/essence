<?php

namespace Elyar\TelegramBotEssentials\Models\Billing\Attempts;

use Elyar\TelegramBotEssentials\Models\Abstract\PaymentAttempt;
use Elyar\TelegramBotEssentials\Models\Billing\Payment;
use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToCardAttempt extends PaymentAttempt
{
    use HasMessageMeta;
    protected $guarded = [
        'id',
        'updated_at',
        'deleted_at',
        'created_at',
    ];

    protected function attemptSucceedHook(): void
    {
        // TODO: Implement attemptSucceedHook() method.
    }

    protected function attemptFailedHook(): void
    {
        $this->setAttribute('rejected_at', now());
        $this->save();
    }
}
