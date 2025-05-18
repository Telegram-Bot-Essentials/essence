<?php

namespace Elyar\TelegramBotEssentials\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CurrencyFather
{
    private string $currency;

    private string $toCurrency;
    private float $amount;

    public function __construct(string $currency)
    {
        $this->currency = $currency;
    }

    public function amount(float $amount = 1): self
    {
        $this->amount = $amount;
        return $this;
    }

    public static function from(string $currency): self
    {
        return new self($currency);
    }

    public static function USD(): self
    {
        return new self('USD');
    }

    public function toUSD(): float
    {
        $this->toCurrency = 'USD';
        return $this->rate();
    }

    public static function IRR(): self
    {
        return new self('IRR');
    }

    public function toIRR(): float
    {
        $this->toCurrency = 'IRR';
        return $this->rate();
    }

    public static function IRT(): self
    {
        return new self('IRT');
    }

    public function toIRT(): float
    {
        $this->toCurrency = 'IRT';
        return $this->rate();
    }

    /**
     * @throws ConnectionException
     */
    public function rate(): float
    {
        $url = 'https://currency.servicefather.ir/api/currencies/' . $this->toCurrency . '/' . $this->currency . '/' . $this->amount;
        $response = Http::get($url);
        return $response->json()['data']['rate'];
    }
}
