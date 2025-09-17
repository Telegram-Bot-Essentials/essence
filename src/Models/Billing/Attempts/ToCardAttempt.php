<?php

namespace TelegramBotEssentials\Essence\Models\Billing\Attempts;

use TelegramBotEssentials\Essence\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Payment;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;
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
