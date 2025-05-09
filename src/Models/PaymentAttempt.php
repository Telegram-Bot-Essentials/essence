<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class PaymentAttempt extends Model
{
    use HasMessageMeta;

    protected $appends = ['paid_at', 'failed_at'];
    protected $fillable = ['payment_id', 'attemptable_type', 'attemptable_id', 'status'];

    public function getPaidAtAttribute(): ?Carbon
    {
        $toCardAttempt = $this->toCardAttempt;
        return $toCardAttempt?->accepted_at;
    }

    public function getFailedAtAttribute(): ?Carbon
    {
        $toCardAttempt = $this->toCardAttempt;
        return $toCardAttempt?->rejected_at;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function toCardAttempt(): HasOne
    {
        return $this->hasOne(ToCardAttempt::class);
    }
}

