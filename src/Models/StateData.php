<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

class StateData extends Model
{
    use HasMessageMeta, Prunable;

    protected $table = 'state_data';

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('tbe-essence.pruning.state_data_days', 7)));
    }
}
