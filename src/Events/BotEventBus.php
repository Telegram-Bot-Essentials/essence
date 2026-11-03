<?php

namespace TelegramBotEssentials\Essence\Events;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;
use Throwable;

class BotEventBus
{
    public function listen(string $event, string|array|callable $listener): self
    {
        Event::listen($event, $listener);

        return $this;
    }

    /**
     * Listeners watch what happened: each one runs whatever the others do, and
     * none can stop the update. To refuse a user's request, use a HandlerGate.
     * A failure is still answered as usual, so a listener's dependsOn() reaches
     * the user.
     */
    public function fire(BotEvent $event): void
    {
        if (Event::getFacadeRoot() instanceof EventFake) {
            Event::dispatch($event);

            return;
        }

        $name = $event::class;

        foreach (Event::getListeners($name) as $listener) {
            if (! is_callable($listener)) {
                continue;
            }

            try {
                $listener($name, [$event]);
            } catch (Throwable $e) {
                try {
                    exceptionHandler()->handle($e);
                } catch (Throwable) {
                    // The handler has reported it and ends with an abort, which
                    // must not take the next listener with it.
                }
            }
        }
    }
}
