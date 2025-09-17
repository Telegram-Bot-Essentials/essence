<?php

namespace TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\ZarinPalMethod;

class Verify extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/inquiry.json';

    protected array $data;
    public function __construct(int $amount, string $authority)
    {
        Parent::__construct();

        $this->data['amount'] = $amount;
        $this->data['authority'] = $authority;
    }
}
