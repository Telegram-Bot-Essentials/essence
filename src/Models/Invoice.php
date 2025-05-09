<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $appends = ['status'];
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getStatusAttribute(): string
    {
        $paymentAttempt = $this->paymentAttempt;
        if ($paymentAttempt->paid_at) return 'success';
        if ($paymentAttempt->failed_at) return 'failed';
        return 'pending';
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function paymentAttempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class);
    }

    public function messageMeta(): morphOne
    {
        return $this->morphOne(MessageMeta::class, 'action')->withDefault();
    }

    public function triggerInvoicePaidHook(): void
    {
        dispatch(new FinalizeInvoicePaymentAction(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }
}
