<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Database\factories\InvoiceFactory;
use Elyar\TelegramBotEssentials\Jobs\InvoiceCancelledHookJob;
use Elyar\TelegramBotEssentials\Jobs\InvoiceFailedHookJob;
use Elyar\TelegramBotEssentials\Jobs\InvoicePaidHookJob;
use Elyar\TelegramBotEssentials\Jobs\InvoicePendingHookJob;
use Elyar\TelegramBotEssentials\Traits\HasMessageMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Invoice extends Model
{
    use SoftDeletes;
    use BelongsToTenant;
    use HasFactory;
    use HasMessageMeta;

    protected $guarded = [
        'id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function markAsPaid(): void
    {
        $this->setAttribute('status', 'paid');
        $this->save();
        dispatch(new InvoicePaidHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }

    public function markAsFailed(): void
    {
        $this->setAttribute('status', 'failed');
        $this->save();
        dispatch(new InvoiceFailedHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }

    public function markAsPending(): void
    {
        $this->setAttribute('status', 'pending');
        $this->save();
        dispatch(new InvoicePendingHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }
}
