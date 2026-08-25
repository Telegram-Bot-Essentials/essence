<?php

namespace TelegramBotEssentials\Essence\Contracts;

use TelegramBotEssentials\Essence\Models\Bot;

/**
 * Handlers hold translation keys and resolve them lazily via __(), so
 * something has to decide which locale is active for a given bot before a
 * handler's label is read - both for an incoming webhook update and for a
 * console command (tbe:set-webhook) that has no webhook request to hang a
 * listener off of.
 *
 * Essence binds a default implementation that always returns config('app.locale').
 * A companion package that owns real per-bot locale data (tbe-settings)
 * rebinds this interface in its own service provider instead of essence
 * depending on it - essence never references tbe-settings, or any other
 * companion, by name.
 */
interface ResolvesBotLocale
{
    public function resolve(Bot $bot): string;
}
