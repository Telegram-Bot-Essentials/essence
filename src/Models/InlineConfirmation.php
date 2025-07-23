<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;

class InlineConfirmation extends Model
{
    public $timestamps = false;
    protected $fillable = ['callback_data', 'back_callback_data', 'confirmation_text'];
}
