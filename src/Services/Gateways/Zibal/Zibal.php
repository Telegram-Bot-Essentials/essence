<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\Zibal;


use Elyar\TelegramBotEssentials\Services\Gateways\Zibal\Methods\PaymentRequest;

class Zibal
{
    public function paymentRequest(int $amount, string $callbackUrl): PaymentRequest
    {
        return new PaymentRequest($amount, $callbackUrl);
    }
}
