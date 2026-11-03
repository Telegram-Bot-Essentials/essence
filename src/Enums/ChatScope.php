<?php

namespace TelegramBotEssentials\Essence\Enums;

use TelegramBotEssentials\Essence\Models\Conversation;

/**
 * Where a handler runs: the user's private chat with the bot, group chats
 * (groups, supergroups and their forum topics), or both. Handlers default to
 * Private, so nothing runs in a group unless it opts in.
 */
enum ChatScope: string
{
    case Private = 'private';
    case Groups = 'groups';
    case Any = 'any';

    public function allows(Conversation $conversation): bool
    {
        return match ($this) {
            self::Any => true,
            self::Private => $conversation->isPrivate(),
            self::Groups => ! $conversation->isPrivate(),
        };
    }
}
