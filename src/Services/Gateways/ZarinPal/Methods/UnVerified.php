<?php

namespace TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\ZarinPalMethod;

class UnVerified extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/unVerified.json';

    protected array $data;
}
