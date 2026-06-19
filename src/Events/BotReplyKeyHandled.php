<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotReplyKeyHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $keyText,
    ) {
        parent::__construct($context);
    }
}
