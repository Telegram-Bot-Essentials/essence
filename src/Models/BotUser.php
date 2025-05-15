<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Database\factories\BotUserFactory;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin\AdminPanelKey;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin\BotAdminsKey;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin\BotSettingsKey;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member\CancelProcessKey;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member\MainMenuKey;
use Elyar\TelegramBotEssentials\Traits\CanResolveReplyKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Telegram\Bot\Keyboard\Keyboard;

class BotUser extends Model
{
    use HasFactory;
    use CanResolveReplyKey;
    use SoftDeletes;

    public static function newFactory(): BotUserFactory
    {
        return BotUserFactory::new();
    }
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
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
        } else if ($this->menu == 'main') {
            $rows = array_merge($rows, config('telegram-bot-essentials.keyboard.member'));
            $rows[] = [AdminPanelKey::class];
        } elseif ($this->menu == 'admin') {
            $rows = array_merge($rows, config('telegram-bot-essentials.keyboard.admin'));
            $rows[] = [BotAdminsKey::class,BotSettingsKey::class];
            $rows[] = [MainMenuKey::class];
        }
        return $this->keyboardGenerator($rows);
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

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    private function keyboardGenerator(array $rows): Keyboard
    {
        $replyMarkup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        foreach ($rows as $keys) {
            $processedRow = [];
            foreach ($keys as $key) {
                $resolvedKey = $this->resolveReplyKey($key);
                if(!hasAccess($resolvedKey->getPerm())) continue;
                $processedRow[] = $resolvedKey->getText();
            }
            $replyMarkup->row($processedRow);
        }

        return $replyMarkup;
    }
}
