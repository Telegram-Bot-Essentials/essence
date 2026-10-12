<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Essence\Database\factories\BotUserFactory;
use TelegramBotEssentials\Essence\Services\BotUserStatus;

class BotUser extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /** Telegram delivers to this user. */
    public const STATUS_ACTIVE = 'active';

    /** The user blocked the bot: my_chat_member "kicked", or 403 "bot was blocked by the user". */
    public const STATUS_BLOCKED = 'blocked';

    /** The chat cannot be opened: 400 "chat not found", 403 "bot can't initiate conversation". */
    public const STATUS_UNREACHABLE = 'unreachable';

    /** Valid values of the status column. */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_BLOCKED,
        self::STATUS_UNREACHABLE,
    ];

    /**
     * Not a value of the status column: the Telegram account itself is gone,
     * which is global to the peer rather than per bot, so it is stored on
     * telegram_users.deactivated_at. Named here because it is the fourth
     * reachability state that consumers filter and report on.
     *
     * @see BotUserStatus
     */
    public const STATUS_DEACTIVATED = 'deactivated';

    protected $appends = ['suspend'];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'last_interaction' => 'datetime',
    ];

    public static function newFactory(): BotUserFactory
    {
        return BotUserFactory::new();
    }

    public function getSuspendAttribute(): bool
    {
        return isset($this->suspended_at);
    }

    public function setSuspendAttribute($value): void
    {
        $this->attributes['suspended_at'] = $value ? now() : null;
        $this->save();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_peer_id', 'peer_id');
    }

    /**
     * Users Telegram will actually deliver to. Deliberately says nothing about
     * suspension, which is a policy decision rather than a transport one: use
     * ->reachable()->notSuspended() when both matter.
     */
    public function scopeReachable(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('telegramUser', fn (Builder $telegramUser) => $telegramUser->whereNull('deactivated_at'));
    }

    public function scopeWithStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', (array) $status);
    }

    /**
     * Users whose Telegram account is gone. Independent of the status column,
     * since deactivation is discovered per bot but is true for every bot.
     */
    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->whereHas('telegramUser', fn (Builder $telegramUser) => $telegramUser->whereNotNull('deactivated_at'));
    }

    public function scopeNotSuspended(Builder $query): Builder
    {
        return $query->whereNull('suspended_at');
    }

    public function isReachable(): bool
    {
        return $this->reachability() === self::STATUS_ACTIVE;
    }

    /**
     * The single reachability state of this user, folding the global
     * deactivated flag over the per-bot status column.
     */
    public function reachability(): string
    {
        if ($this->telegramUser?->deactivated_at) {
            return self::STATUS_DEACTIVATED;
        }

        return $this->status ?? self::STATUS_ACTIVE;
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** This user's conversation in one chat (and forum topic), created on first use. */
    public function conversationIn(int $chatId, int $threadId = 0): Conversation
    {
        /** @var Conversation $conversation */
        // Read first; only a miss tries the insert, which the unique key
        // turns into a read if a concurrent update created the row.
        $conversation = $this->conversations()->firstOrCreate(
            ['chat_id' => $chatId, 'thread_id' => $threadId],
            ['bot_id' => $this->getAttribute('bot_id')],
        );

        return $conversation;
    }

    /**
     * This user's private chat with the bot - the conversation to write to
     * when acting on a user other than the one the update came from.
     */
    public function privateConversation(): Conversation
    {
        $peerId = $this->getAttribute('telegram_user_peer_id');

        return $this->conversationIn(is_numeric($peerId) ? (int) $peerId : 0);
    }

    public function interact(): void
    {
        $this->attributes['last_interaction'] = now();
        $this->save();
        $this->telegramUser->interact();
    }
}
