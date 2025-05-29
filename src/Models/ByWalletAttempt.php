<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ByWalletAttempt extends Model
{
    use HasMessageMeta;
    protected $guarded = [
        'id',
        'updated_at',
        'deleted_at',
        'created_at',
    ];

    public function attempt(): void
    {
        if($this->paymentAttempt->invoice->botUser->balance < $this->paymentAttempt->invoice->price) return;
        $this->paymentAttempt->invoice->botUser->balance -= $this->paymentAttempt->invoice->price;
        $this->paymentAttempt->invoice->botUser->save();
        $this->received_at = now();
        $this->save();
        $this->paymentAttempt->invoice->triggerInvoicePaidHook();
        $this->paymentAttempt->invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }
}
