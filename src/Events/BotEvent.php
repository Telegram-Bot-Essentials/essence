<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

abstract class BotEvent
{
    public function __construct(
        public readonly WebhookContext $context,
    ) {}
}
