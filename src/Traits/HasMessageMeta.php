<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMessageMeta
{
    public function messageMeta(): morphOne
    {
        return $this->morphOne(MessageMeta::class, 'action')->withDefault();
    }
}
