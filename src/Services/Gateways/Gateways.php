<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways;

use Elyar\TelegramBotEssentials\Services\Gateways\ZarinPal\ZarinPal;
use Elyar\TelegramBotEssentials\Services\Gateways\Zibal\Zibal;

class Gateways
{
    public function zibal(): Zibal
    {
        return app(Zibal::class);
    }

    public function zarinpal(): Zarinpal
    {
        return app(Zarinpal::class);
    }
}