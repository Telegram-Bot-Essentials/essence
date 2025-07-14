<?php

namespace Elyar\TelegramBotEssentials\Services;

use InvalidArgumentException;

class Currency
{
    private ?string $currency = "USD";
    private string $amount;

    public function __construct()
    {

    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        if(!$this->isCurrencySupported($currency)){
            throw new InvalidArgumentException('Unsupported currency');
        }

        $this->currency = $currency;
    }

    public function amount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function show(): string
    {
        return $this->amount;
    }

    public function getSupportedCurrencies(): array
    {
        return collect(config('telegram-bot-essentials.supported_currencies'))->pluck('name')->toArray();
    }

    public function isCurrencySupported(string $currency): bool
    {
        return in_array($currency, currency()->getSupportedCurrencies());
    }
}
