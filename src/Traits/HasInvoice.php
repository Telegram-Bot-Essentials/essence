<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasInvoice
{
    public function invoice(): MorphOne
    {
        return $this->morphOne(Invoice::class, 'payable')->latest();
    }
    abstract public function invoicePaidHook(): void;
    abstract public function invoiceCancelledHook(): void;
    abstract public function invoicePendingHook(): void;
    abstract public function invoiceFailedHook(): void;
}
