<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Model;

class InlineConfirmation extends Model
{
    protected $fillable = ['callback_data', 'back_callback_data', 'confirmation_text'];
}
