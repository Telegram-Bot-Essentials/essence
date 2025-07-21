<?php

namespace Elyar\TelegramBotEssentials\Services\Gateways\Zibal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class ZibalMethod
{
    protected string $url;
    protected array $data;

    public function __construct()
    {
    }

    public function execute()
    {
        $this->data['merchant'] = wHook()->bot()->settings->zibal_merchant;
        $result = HTTP::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->url, $this->data);
        return $result->json();
    }
}
