<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use TelegramBotEssentials\Essence\Database\factories\BotFactory;
use Throwable;

/**
 * @property string $unique_id
 * @property string|null $secret_token
 */
class Bot extends BaseTenant
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bots';

    protected $appends = ['suspended'];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'secret_token',
    ];

    protected $casts = [
        'activated_until' => 'datetime',
        'suspended_at' => 'datetime',
        'suspended' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'data' => 'array',
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

    public function botOwner(): HasOne
    {
        return $this->hasOne(TelegramUser::class, 'peer_id', 'bot_owner_peer_id');
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'unique_id',
            'bot_token',
            'secret_token',
            'currency',
            'bot_owner_peer_id',
            'activated_until',
            'suspended_at',
            'updated_at',
            'created_at',
            'deleted_at',
        ];
    }

    public function getIncrementing(): true
    {
        return true;
    }

    /**
     * The bot's username without the @, from getMe, cached for a day per
     * token. Null when Telegram cannot be reached; callers then treat a
     * `/command@name` as addressed to this bot.
     */
    public function username(): ?string
    {
        $token = $this->getAttribute('bot_token');
        if (! is_string($token) || $token === '') {
            return null;
        }

        $id = $this->getKey();
        $key = 'tbe:bot-username:'.(is_scalar($id) ? $id : '').':'.md5($token);

        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $username = telegramApi($token)->getMe()->username;
        } catch (Throwable) {
            return null;
        }

        if (! is_string($username) || $username === '') {
            return null;
        }

        Cache::put($key, $username, now()->addDay());

        return $username;
    }
}
