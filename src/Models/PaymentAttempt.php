<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

abstract class PaymentAttempt extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function invoice(): MorphOne
    {
        return $this->morphOne(Invoice::class, 'payment_attempt');
    }

    public function attemptSucceed(): void
    {
        $this->attemptSucceedHook();
        $this->status = 'succeed';
        $this->save();
        $this->invoice->markAsPaid();
    }

    public function attemptFailed(): void
    {
        $this->attemptFailedHook();
        $this->status = 'failed';
        $this->save();
        $this->invoice->markAsFailed();
    }

    abstract public function attemptSucceedHook(): void;
    abstract public function attemptFailedHook(): void;
}

