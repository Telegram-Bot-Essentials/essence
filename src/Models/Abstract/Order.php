<?php

namespace Elyar\TelegramBotEssentials\Models\Abstract;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Telegram\Features\Member\MyWalletFeature;
use Elyar\TelegramBotEssentials\Traits\HasInvoice;
use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Telegram\Bot\Exceptions\TelegramSDKException;

abstract class Order extends Model
{
    use BelongsToTenant;
    use HasMessageMeta;
    use HasInvoice;

    protected $appends = ['amount', 'description', 'paid_at'];
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    abstract public function getPaidAtAttribute(): ?Carbon;

    abstract public function getAmountAttribute(): string;

    abstract public function getDescriptionAttribute(): string;
}
