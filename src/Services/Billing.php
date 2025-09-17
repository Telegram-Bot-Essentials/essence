<?php

namespace TelegramBotEssentials\Essence\Services;

use TelegramBotEssentials\Essence\Models\Abstract\Order;
use TelegramBotEssentials\Essence\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Invoice;

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
