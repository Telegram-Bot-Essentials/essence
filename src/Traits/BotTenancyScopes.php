<?php

namespace Elyar\TelegramBotEssentials\Traits;


use Elyar\TelegramBotEssentials\Models\Bot;

trait BotTenancyScopes
{
    public function scopeForCurrentBot($query)
    {
        return $query->where('bot_id', Bot::current()->id);
    }
}
