<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A group or supergroup the bot has been added to, and whether it may use the
 * bot (see GroupChats).
 *
 * @property int $id
 * @property int $bot_id
 * @property int $chat_id
 * @property string $type
 * @property string|null $title
 * @property string|null $username
 * @property string $bot_status member, administrator, restricted, left or kicked
 * @property Carbon|null $approved_at
 */
class BotChat extends Model
{
    use BelongsToTenant;

    private const PRESENT_STATUSES = ['member', 'administrator', 'creator', 'restricted'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public static function isPresentStatus(?string $status): bool
    {
        return in_array($status, self::PRESENT_STATUSES, true);
    }

    /**
     * @param  Builder<BotChat>  $query
     * @return Builder<BotChat>
     */
    public function scopePresent(Builder $query): Builder
    {
        return $query->whereIn('bot_status', self::PRESENT_STATUSES);
    }
}
