<?php

namespace TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\ZarinPalMethod;

class ReverseTransaction extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/reverse.json';

    protected array $data;
    public function __construct(string $authority)
    {
        Parent::__construct();

        $this->data['authority'] = $authority;
    }
}
