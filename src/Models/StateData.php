<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Model;

class StateData extends Model
{
    use HasMessageMeta;

    public $timestamps = false;

    protected $table = 'state_data';
    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];
}