<?php

namespace TelegramBotEssentials\Essence\Contracts;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Models\Conversation;

/**
 * Builds the reply markup sent alongside a bot's messages in a
 * conversation: one user in one chat, where the state and menu live.
 *
 * replyKeyboard() delegates here, so every call site picks up whichever
 * implementation is bound. Essence binds
 * DefaultKeyboardResolver (named menus from config, the active state
 * answer's own keyboard, or no keyboard at all); an app or companion that
 * wants a different menu model rebinds this interface.
 */
interface ResolvesKeyboard
{
    /** @return Keyboard<array-key, mixed> */
    public function for(Conversation $conversation): Keyboard;
}
