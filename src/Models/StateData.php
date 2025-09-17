<?php

namespace TelegramBotEssentials\Essence\Models;

use TelegramBotEssentials\Essence\Traits\HasMessageMeta;
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