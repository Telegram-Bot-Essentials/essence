<?php

namespace TelegramBotEssentials\Essence\Contracts;

use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;

/**
 * A condition on what a user may ask of the bot: their reply keys, button
 * presses, form answers and commands. Checked once a handler has claimed the
 * update, so it does not touch anything else: event listeners, messages the bot
 * sends by itself, or chatter no handler is interested in.
 */
interface HandlerGate
{
    /**
     * @throws FeatureIsDisabled to stop $handler, telling the user what its response says
     */
    public function check(object $handler): void;
}
