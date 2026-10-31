<?php

namespace TelegramBotEssentials\Essence\Contracts;

/**
 * A handler that names the Gate ability it needs, or null when anyone may
 * use it.
 */
interface DeclaresAbility
{
    public function getAbility(): ?string;
}
