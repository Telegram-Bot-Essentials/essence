<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\Methods;

use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\ZarinPalMethod;

class Inquiry extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/inquiry.json';

    protected array $data;
    public function __construct(string $authority)
    {
        Parent::__construct();

        $this->data['authority'] = $authority;
    }
}
