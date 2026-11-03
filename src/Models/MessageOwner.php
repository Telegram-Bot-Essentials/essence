<?php

namespace TelegramBotEssentials\Essence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * The user a group message with inline buttons was sent for. Recorded as the
 * message goes out (LaravelHttpClient) and checked when a button on it is
 * pressed (CallbackQueryBus), so only that user can press it.
 *
 * @property int $chat_id
 * @property int $message_id
 * @property int $bot_user_id
 */
class MessageOwner extends Model
{
    use BelongsToTenant;
    use Prunable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function record(int $chatId, int $messageId, int $botUserId): void
    {
        static::query()->updateOrCreate(
            ['chat_id' => $chatId, 'message_id' => $messageId],
            ['bot_user_id' => $botUserId],
        );
    }

    /** The owner's bot user id, or null when the message has none on record. */
    public static function ownerOf(int $chatId, int $messageId): ?int
    {
        $owner = static::query()->where('chat_id', $chatId)->where('message_id', $messageId)->value('bot_user_id');

        return is_numeric($owner) ? (int) $owner : null;
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subDays(config()->integer('tbe-essence.pruning.message_owners_days', 7)));
    }
}
