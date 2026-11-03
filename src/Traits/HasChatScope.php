<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Enums\ChatScope;

/**
 * Lets a handler say which chats it runs in, e.g.
 * `protected ChatScope $chatScope = ChatScope::Any;`. Left alone, it runs in
 * the user's private chat only.
 */
trait HasChatScope
{
    protected ChatScope $chatScope = ChatScope::Private;

    public function getChatScope(): ChatScope
    {
        return $this->chatScope;
    }
}
