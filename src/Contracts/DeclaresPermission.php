<?php

namespace TelegramBotEssentials\Essence\Contracts;

/**
 * A handler that names the permission it needs, or null when anyone may use
 * it.
 */
interface DeclaresPermission
{
    public function getPermission(): ?string;
}
