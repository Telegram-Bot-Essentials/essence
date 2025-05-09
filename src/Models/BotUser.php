<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Traits\CanResolveReplyKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Telegram\Bot\Keyboard\Keyboard;

class BotUser extends Model
{
    use CanResolveReplyKey;
    use SoftDeletes;

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
        } else if ($this->menu == 'main') {

        } elseif ($this->menu == 'admin') {
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
                if ($resolvedKey->getPerm() > $this->power) continue;
                $processedRow[] = $resolvedKey->getText();
            }
            $replyMarkup->row($processedRow);
        }

        return $replyMarkup;
    }
}
