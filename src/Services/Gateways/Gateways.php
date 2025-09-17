<?php

namespace TelegramBotEssentials\Essence\Services\Gateways;

use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Services\Gateways\ZarinPal\ZarinPal;
use TelegramBotEssentials\Essence\Services\Gateways\Zibal\Zibal;

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

    public function wallet(): Wallet
    {
        return app(Wallet::class);
    }
}