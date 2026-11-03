<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Essence\Contracts\RecipientGroups;
use TelegramBotEssentials\Essence\Models\Bot;

/** What a bot has until a package brings groups of its own. */
class NoRecipientGroups implements RecipientGroups
{
    public function options(Bot $bot): array
    {
        return [];
    }

    public function members(Bot $bot, array $ids): Collection
    {
        return new Collection;
    }
}
