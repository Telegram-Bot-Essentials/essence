<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\BelongsToBot;
use Elyar\TelegramBotEssentials\Traits\BotTenancyScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class BotSettings extends Model
{
    use SoftDeletes;
    use BelongsToBot;
    use BotTenancyScopes;

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function setPayToCardNumberAttribute($value): void
    {
        Validator::validate(['card_number' => $value], ['card_number' => 'required|digits:16']);
        $this->attributes['pay_to_card_number'] = $value;
    }

    public function setPayToCardNameAttribute($value): void
    {
        Validator::validate(['card_name' => $value], ['card_name' => 'required|string']);
        $this->attributes['pay_to_card_name'] = $value;
    }

    public function setTransactionsChatIdAttribute($value): void
    {
        Validator::validate(['transactions_chat_id' => $value], ['transactions_chat_id' => 'required|integer']);
        $this->attributes['transactions_chat_id'] = $value;
    }

    public function setConnectionGuideChannelIdAttribute($value): void
    {
        Validator::validate(['connection_guide_channel_id' => $value], ['connection_guide_channel_id' => 'required|string|max:64']);
        $this->attributes['connection_guide_channel_id'] = $value;
    }
}
