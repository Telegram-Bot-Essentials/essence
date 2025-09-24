<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Database\factories\BotUserFactory;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\AdminPanelKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\BotAdminsKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\BotSettingsKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\ManageInvoicesKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\MainMenuKey;
use TelegramBotEssentials\Essence\Traits\CanResolveReplyKey;

class BotUser extends Model
{
    use HasFactory;
    use CanResolveReplyKey;
    use BelongsToTenant;

    protected $appends = ['role', 'suspend'];
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
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
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function getKeyboard(): Keyboard
    {
        $rows = [];
        if ($this->state) {
        } else if ($this->menu == 'main') {
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
                if (!hasAccess($resolvedKey->getPerm())) continue;
                if (in_array($resolvedKey->getText(), $addedKeys)) continue;
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
}
