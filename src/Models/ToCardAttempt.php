<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToCardAttempt extends Model
{
    use HasMessageMeta;
    protected $guarded = [
        'id',
        'updated_at',
        'deleted_at',
        'created_at',
    ];

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
