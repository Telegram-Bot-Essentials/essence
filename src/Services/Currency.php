<?php

namespace Elyar\TelegramBotEssentials\Services;

class Currency
{
    private ?string $currency = "USD";
    private string $amount;

    public function __construct()
    {

    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
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
}
