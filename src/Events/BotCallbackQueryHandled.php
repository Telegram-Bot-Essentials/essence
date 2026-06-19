<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotCallbackQueryHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $type,
        public readonly string $method,
    ) {
        parent::__construct($context);
    }
}
