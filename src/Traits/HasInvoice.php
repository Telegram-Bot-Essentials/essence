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
}
