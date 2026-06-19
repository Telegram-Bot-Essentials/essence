<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotStateAnswerHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $state,
    ) {
        parent::__construct($context);
    }
}
