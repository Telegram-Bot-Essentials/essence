<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotCommandHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $command,
    ) {
        parent::__construct($context);
    }
}
