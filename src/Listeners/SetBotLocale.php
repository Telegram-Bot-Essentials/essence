<?php

namespace TelegramBotEssentials\Essence\Listeners;

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;

class SetBotLocale
{
    public function __construct(private ResolvesBotLocale $resolver) {}

    public function handle(BotWebhookInitialized $event): void
    {
        $bot = $event->context->resolveBot();

        if ($bot === null) {
            return;
        }

        App::setLocale($this->resolver->resolve($bot));
    }
}
