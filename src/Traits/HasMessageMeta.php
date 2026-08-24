<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use TelegramBotEssentials\Essence\Models\MessageMeta;

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
