<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotDeepLinkReceived extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $payload,
    ) {
        parent::__construct($context);
    }
}
