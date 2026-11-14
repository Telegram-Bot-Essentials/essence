<?php

namespace TelegramBotEssentials\Essence\Contracts;

use TelegramBotEssentials\Essence\Support\Label;

/**
 * Something with a name for both the bot and the web panel. Implement it next
 * to an older string label() so each side can pick its own wording.
 */
interface Labelled
{
    public function labelled(): Label;
}
