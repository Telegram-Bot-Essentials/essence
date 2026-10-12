<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * One user in one chat (and forum topic): where a flow's state and the
 * reply menu live. A user's private chat with the bot is the conversation
 * whose chat_id is their peer id.
 *
 * @property int $id
 * @property int $bot_id
 * @property int $bot_user_id
 * @property int $chat_id
 * @property int $thread_id 0 outside a forum topic
 * @property string|null $state
 * @property string $menu
 */
class Conversation extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $attributes = [
        'thread_id' => 0,
        'menu' => 'main',
    ];

    /** @return BelongsTo<BotUser, $this> */
    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function isPrivate(): bool
    {
        $peerId = $this->botUser?->getAttribute('telegram_user_peer_id');

        return is_numeric($peerId) && (int) $this->chat_id === (int) $peerId;
    }

    public function changeState(?string $state = null): void
    {
        $this->state = $state;
        $this->save();
    }

    /** @param  array<string, mixed>  $params */
    public function addParamToState(array $params): void
    {
        $state = decodeAnswerState($this->state);

        if ($state['type'] === null) {
            return;
        }

        $this->state = encodeAnswerState($state['type'], $state['method'], array_merge($state['params'], $params));
        $this->save();
    }

    public function changeMenu(string $menu): void
    {
        $this->menu = $menu;
        $this->save();
    }
}
