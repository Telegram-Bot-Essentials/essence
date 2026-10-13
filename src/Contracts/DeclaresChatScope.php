<?php

namespace TelegramBotEssentials\Essence\Contracts;

use TelegramBotEssentials\Essence\Enums\ChatScope;

/**
 * A handler that says which chats it runs in. A handler that does not
 * implement this runs in private chats only.
 */
interface DeclaresChatScope
{
    public function getChatScope(): ChatScope;
}
