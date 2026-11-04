<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * A BotUser signed in on the web. Still a BotUser, so the Gate and
 * every service keep working; there are no passwords and no second user table.
 */
class WebUser extends BotUser implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'bot_users';

    /** Keys and morph names Laravel infers from the class must stay BotUser's. */
    public function getForeignKey(): string
    {
        return 'bot_user_id';
    }

    public function getMorphClass(): string
    {
        return (new BotUser)->getMorphClass();
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
