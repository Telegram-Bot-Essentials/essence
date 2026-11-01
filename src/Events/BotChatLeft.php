<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Models\BotChat;

/** The bot left or was removed from a group. Fired without a user or webhook context. */
class BotChatLeft
{
    public function __construct(public readonly BotChat $chat) {}
}
