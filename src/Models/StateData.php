<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;

class StateData extends Model
{
    public $timestamps = false;

    protected $table = 'state_data';
    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];
}