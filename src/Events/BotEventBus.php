<?php

namespace TelegramBotEssentials\Essence\Events;

use Illuminate\Support\Facades\Event;

class BotEventBus
{
    public function listen(string $event, string|array|callable $listener): self
    {
        Event::listen($event, $listener);
        return $this;
    }

    public function fire(BotEvent $event): void
    {
        Event::dispatch($event);
    }
}
