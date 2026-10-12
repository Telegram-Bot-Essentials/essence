<?php

namespace TelegramBotEssentials\Essence\Contracts;

use Illuminate\Database\Eloquent\Builder;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Decides whether a user may use something that asks for a permission:
 * a handler, a reply key on a menu, a form.
 *
 * $permission is a registered permission name (see PermissionRegistry), or
 * null for "anyone". Every bus funnels its check through here, so swapping
 * the binding swaps the access model for the whole bot.
 *
 * Essence binds DefaultAuthorizer (the owner and the developer only). A
 * companion package that owns real roles rebinds this interface in its own
 * service provider; essence never references it by name.
 */
interface AuthorizesAccess
{
    public function allows(BotUser $user, ?string $permission): bool;

    /**
     * The bot's users granted $permission, for when essence has to reach
     * them rather than check them (admin alerts). The owner and developer,
     * who hold everything without a grant, are left to the caller.
     *
     * @return Builder<BotUser>
     */
    public function usersWith(Bot $bot, string $permission): Builder;
}
