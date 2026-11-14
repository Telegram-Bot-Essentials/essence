<?php

namespace TelegramBotEssentials\Essence\Contracts;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Named groups of a bot's users that a package can address without knowing what
 * they are. A package that has such groups binds its own; essence has none.
 */
interface RecipientGroups
{
    /**
     * The groups to choose from, for showing in a picker.
     *
     * @return array<string, string> group id => label
     */
    public function options(Bot $bot): array;

    /**
     * Everyone in any of these groups who can be messaged privately, once each
     * however many of the groups they are in. Unknown ids are ignored.
     *
     * @param  list<string>  $ids
     * @return Collection<int, BotUser>
     */
    public function members(Bot $bot, array $ids): Collection;
}
