<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Model;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

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
