<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal;

use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods\Inquiry;
use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods\PaymentRequest;
use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods\ReverseTransaction;
use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods\UnVerified;
use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods\Verify;

class ZarinPal
{
    public function Inquiry(string $authority): Inquiry
    {
        return new Inquiry($authority);
    }

    public function PaymentRequest(int $amount, string $description, string $callback_url): PaymentRequest
    {
        return new PaymentRequest($amount, $description, $callback_url);
    }

    public function ReverseTransaction(string $authority): ReverseTransaction
    {
        return new ReverseTransaction($authority);
    }

    public function UnVerified(): UnVerified
    {
        return new UnVerified();
    }

    public function Verify(int $amount, string $authority): Verify
    {
        return new Verify($amount, $authority);
    }
}
