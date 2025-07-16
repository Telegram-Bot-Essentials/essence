<?php

namespace Elyar\TelegramBotEssentials\Services;

use Elyar\TelegramBotEssentials\Models\Abstract\Order;
use Elyar\TelegramBotEssentials\Models\Abstract\PaymentAttempt;
use Elyar\TelegramBotEssentials\Models\Billing\Invoice;

class Billing
{
    public function createInvoice(Order $order): Invoice
    {
        return $order->invoice()->create([
            'bot_user_id' => $order->botUser->id,
            'price' => $order->amount
        ]);
    }

    public function attemptPayment(Invoice $invoice, PaymentAttempt $paymentAttempt): void
    {
        $invoice->paymentAttempt()->associate($paymentAttempt);
        $invoice->save();
    }
}
