<?php

namespace TelegramBotEssentials\Essence\Traits;

/**
 * Lets a handler name the Gate ability it needs, e.g.
 * `protected ?string $ability = 'billing.offers.manage';`. Left null, the
 * handler is open to everyone. The app decides who holds an ability with
 * Laravel's Gate, called with the BotUser.
 */
trait HasAbility
{
    protected ?string $ability = null;

    public function getAbility(): ?string
    {
        return $this->ability;
    }
}
