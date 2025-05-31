<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

abstract class PaymentAttempt extends Model
{
    abstract public function attempt(): void;

    public function isConfirmed(): ?Carbon
    {
        return $this->confirmed_at;
    }

    public function isFailed(): ?Carbon
    {
        return $this->failed_at;
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payments::class);
    }
}


