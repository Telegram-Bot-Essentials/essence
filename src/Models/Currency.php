<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function botSettings(): BelongsTo
    {
        return $this->belongsTo(BotSettings::class);
    }
}
