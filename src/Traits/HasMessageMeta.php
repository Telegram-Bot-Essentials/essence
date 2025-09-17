<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Models\MessageMeta;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMessageMeta
{
    public function messageMeta(): MorphOne
    {
        return $this->morphOne(MessageMeta::class, 'action')->latest()->withDefault();
    }

    public function allMessageMetas(): MorphMany
    {
        return $this->morphMany(MessageMeta::class, 'action');
    }
}
