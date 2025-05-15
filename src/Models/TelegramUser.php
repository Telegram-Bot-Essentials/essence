<?php

namespace Elyar\TelegramBotEssentials\Models;


use Elyar\TelegramBotEssentials\Database\factories\TelegramUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramUser extends Model
{
    use HasFactory;
    use SoftDeletes;

    public static function newFactory(): TelegramUserFactory
    {
        return TelegramUserFactory::new();
    }

    protected $appends = ['full_name'];

    protected $fillable = [
        'name',
        'email',
        'peer_id',
        'first_name',
        'last_name',
        'username',
        'password',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function botUsers(): hasMany
    {
        return $this->hasMany(BotUser::class);
    }
}
