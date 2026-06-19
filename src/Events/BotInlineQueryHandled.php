<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotInlineQueryHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $query,
    ) {
        parent::__construct($context);
    }
}
