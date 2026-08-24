<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class InlineConfirmation extends Model
{
    use Prunable;

    protected $fillable = ['callback_data', 'back_callback_data', 'confirmation_text'];

    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subHours(config('tbe-essence.pruning.inline_confirmations_hours', 6)));
    }
}
