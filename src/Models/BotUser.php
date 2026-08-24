<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Database\factories\BotUserFactory;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Services\BotUserStatus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Traits\CanResolveReplyKey;

class BotUser extends Model
{
    use BelongsToTenant;
    use CanResolveReplyKey;
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

    protected $appends = ['role', 'suspend'];

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

    public function getRoleAttribute(): string
    {
        switch ($this->power) {
            case Roles::ADMIN->value:
                return __('tbe::general.roles.admin');
            case Roles::MODERATOR->value:
                return __('tbe::general.roles.moderator');
            default:
                return __('tbe::general.roles.member');
        }
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

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function getKeyboard(): Keyboard
    {
        $rows = [];
        if ($this->state) {
            $rows[] = [CancelProcessKey::class];
        } elseif ($this->menu == 'main') {
            $rows = array_merge($rows, config('tbe-essence.keyboard.member') ?? []);
        } elseif ($this->menu == 'admin') {
            $rows = array_merge($rows, config('tbe-essence.keyboard.admin') ?? []);
        }

        return $this->keyboardGenerator($rows);
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    private function keyboardGenerator(array $rows): Keyboard
    {
        $replyMarkup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        $addedKeys = [];
        foreach ($rows as $keys) {
            $processedRow = [];
            foreach ($keys as $key) {
                $resolvedKey = $this->resolveReplyKey($key);
                if (! hasAccess($resolvedKey->getPerm())) {
                    continue;
                }
                if (! $resolvedKey->isEnabled()) {
                    continue;
                }
                if (in_array($resolvedKey->getText(), $addedKeys)) {
                    continue;
                }
                $addedKeys[] = $resolvedKey->getText();
                $processedRow[] = $resolvedKey->getText();
            }
            $replyMarkup->row($processedRow);
        }

        return $replyMarkup;
    }

    public function changeState(?string $state = null): void
    {
        $this->attributes['state'] = $state;
        $this->save();
    }

    public function addParamToState(array $params): void
    {
        $state = decodeAnswerState($this->attributes['state']);
        $this->attributes['state'] = encodeAnswerState($state['type'], $state['method'], array_merge($state['params'], $params));
        $this->save();
    }

    public function interact(): void
    {
        $this->attributes['last_interaction'] = now();
        $this->save();
        $this->telegramUser->interact();
    }
}
