<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\Zibal;


use Elyar\TelegramBotEssentials\Services\Gateways\Zibal\Methods\PaymentRequest;
use Elyar\TelegramBotEssentials\Services\Gateways\Zibal\Methods\Verify;

class Zibal
{
    public function paymentRequest(int $amount, string $callbackUrl): PaymentRequest
    {
        return new PaymentRequest($amount, $callbackUrl);
    }

    public function verify(string $trackId): Verify
    {
        return new Verify($trackId);
    }
}
