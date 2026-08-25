<?php

namespace TelegramBotEssentials\Essence\Support;

use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;

/**
 * Essence itself has no notion of a per-bot locale, so this always returns
 * the app's own configured locale. A companion package that does (e.g.
 * tbe-settings, backed by its own settings store) rebinds ResolvesBotLocale
 * to its own implementation.
 */
class DefaultBotLocaleResolver implements ResolvesBotLocale
{
    public function resolve(Bot $bot): string
    {
        return config()->string('app.locale');
    }
}
