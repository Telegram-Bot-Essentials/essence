<?php

namespace Elyar\TelegramBotEssentials\Traits;

trait HidesDone
{
    public function scopeDone($query)
    {
        return $query->where('done', true);
    }
}
