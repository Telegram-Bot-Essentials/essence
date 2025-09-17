<?php

namespace TelegramBotEssentials\Essence\Traits;

trait HidesDone
{
    public function scopeDone($query)
    {
        return $query->where('done', true);
    }
}
