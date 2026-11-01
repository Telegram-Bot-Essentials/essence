<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Support\Facades\DB;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\MessageOwner;

/**
 * A group became a supergroup, which gives it a new id. Open flows and button
 * owners follow it. Telegram sends one message from each chat, in no promised
 * order, with traffic possibly in between, so this is idempotent and keeps what
 * already exists under the new id.
 */
class GroupChatMigration
{
    public function run(int $oldChatId, int $newChatId): void
    {
        DB::transaction(function () use ($oldChatId, $newChatId) {
            Conversation::where('chat_id', $oldChatId)->get()->each(function (Conversation $conversation) use ($newChatId) {
                $taken = Conversation::where('bot_user_id', $conversation->bot_user_id)
                    ->where('chat_id', $newChatId)
                    ->where('thread_id', $conversation->thread_id)
                    ->exists();

                $taken ? $conversation->delete() : $conversation->forceFill(['chat_id' => $newChatId])->save();
            });

            MessageOwner::where('chat_id', $oldChatId)->get()->each(function (MessageOwner $owner) use ($newChatId) {
                $taken = MessageOwner::where('chat_id', $newChatId)->where('message_id', $owner->message_id)->exists();

                $taken ? $owner->delete() : $owner->forceFill(['chat_id' => $newChatId])->save();
            });

            // Not scoped to a bot: the table has no bot column, and a chat's new id
            // holds for every bot in it.
            MessageMeta::where('chat_id', $oldChatId)->update(['chat_id' => $newChatId]);
        });
    }
}
