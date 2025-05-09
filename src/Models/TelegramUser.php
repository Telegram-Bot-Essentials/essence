<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramUser extends Model
{
    use SoftDeletes;

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
