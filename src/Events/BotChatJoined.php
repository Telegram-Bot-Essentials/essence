<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Models\BotChat;

/** The bot was added to a group. Fired without a user or webhook context: nobody is minted for it. */
class BotChatJoined
{
    public function __construct(public readonly BotChat $chat) {}
}
