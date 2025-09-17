<?php

namespace TelegramBotEssentials\Essence\Services\Gateways\Zibal\Methods;

use TelegramBotEssentials\Essence\Services\Gateways\Zibal\ZibalMethod;

class Verify extends ZibalMethod
{
    protected string $url = 'https://gateway.zibal.ir/v1/verify';

    public function __construct(string $trackId)
    {
        Parent::__construct();

        $this->data['trackId'] = $trackId;
    }
}
