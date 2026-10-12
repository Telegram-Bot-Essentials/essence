<?php

namespace TelegramBotEssentials\Essence\Traits;

/**
 * Lets a handler name the permission it needs, e.g.
 * `protected ?string $permission = 'billing.offers.manage';`. Left null, the
 * handler is open to everyone.
 */
trait HasPermission
{
    protected ?string $permission = null;

    public function getPermission(): ?string
    {
        return $this->permission;
    }
}
