<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotUpdateReceived extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $updateType,
    ) {
        parent::__construct($context);
    }
}
