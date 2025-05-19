<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Database\factories\BotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Bot extends Model implements IsTenant
{
    use HasFactory;
    use SoftDeletes;
    use UsesLandlordConnection;
    use ImplementsTenant;

    protected $appends = ['suspended'];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'bot_token',
        'secret_token'
    ];

    protected $casts = [
        'activated_until' => 'datetime',
        'suspended_at' => 'datetime',
        'suspended' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getSuspendedAttribute(): bool
    {
        return $this->suspended_at != null;
    }

    public static function newFactory(): BotFactory
    {
        return BotFactory::new();
    }

    public function botUsers(): HasMany
    {
        return $this->hasMany(BotUser::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(BotSettings::class)->withDefault();
    }

    public function botOwner(): HasOne
    {
        return $this->hasOne(TelegramUser::class, 'peer_id', 'bot_owner_peer_id');
    }
}
