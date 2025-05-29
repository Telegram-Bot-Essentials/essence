<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Traits\HasInvoice;
use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CreditOrder extends Model
{
    use BelongsToTenant;
    use HasMessageMeta;
    use HasInvoice;

    protected $appends = ['price', 'description', 'paid_at'];
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'price' => 'int',
    ];

    public function getPaidAtAttribute(): ?Carbon
    {
        return $this->invoice?->paid_at;
    }

    public function getPriceAttribute(): float
    {
        return formatFloat($this->timePlan?->price + $this->quotaPlan?->price);
    }

    public function getDescriptionAttribute(): string
    {
        return 'develop';
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    public function invoicePaidHook(): void
    {
        wHook()->user()->balance += $this->amount;
        wHook()->user()->save();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => 'Your total credit increased by ' . priceFormat($this->amount) . ' 💸',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
