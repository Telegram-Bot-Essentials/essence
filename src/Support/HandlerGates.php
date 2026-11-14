<?php

namespace TelegramBotEssentials\Essence\Support;

use TelegramBotEssentials\Essence\Contracts\HandlerGate;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;

/** The gates every handler passes before it runs. A singleton: packages register at boot. */
class HandlerGates
{
    /** @var array<class-string<HandlerGate>, class-string<HandlerGate>> */
    private array $gates = [];

    /**
     * @param  class-string<HandlerGate>  $gate
     */
    public function add(string $gate): self
    {
        $this->gates[$gate] = $gate;

        return $this;
    }

    /**
     * @throws FeatureIsDisabled
     */
    public function check(object $handler): void
    {
        foreach ($this->gates as $gate) {
            $instance = app()->make($gate);

            if ($instance instanceof HandlerGate) {
                $instance->check($handler);
            }
        }
    }
}
