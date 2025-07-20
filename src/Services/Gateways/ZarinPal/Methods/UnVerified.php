<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods;

use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\ZarinPalMethod;

class UnVerified extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/unVerified.json';

    protected array $data;
}
