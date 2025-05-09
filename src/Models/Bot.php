<?php

namespace Elyar\TelegramBotEssentials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Bot extends Model implements IsTenant
{
    use SoftDeletes;
    use UsesLandlordConnection;
    use ImplementsTenant;

    protected $fillable = [

    ];

    protected $hidden = [
        'bot_token',
        'secret_token'
    ];

    public function botUsers(): HasMany
    {
        return $this->hasMany(BotUser::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(BotSetting::class)->withDefault();
    }
}
