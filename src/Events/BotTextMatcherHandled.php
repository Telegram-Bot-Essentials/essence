<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;

class BotTextMatcherHandled extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly string $matcher,
    ) {
        parent::__construct($context);
    }
}
