<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\ChatMemberUpdated;
use TelegramBotEssentials\Essence\Events\BotChatJoined;
use TelegramBotEssentials\Essence\Events\BotChatLeft;
use TelegramBotEssentials\Essence\Models\BotChat;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\MessageOwner;

/**
 * The groups a bot has been added to. Telegram gives no say in who adds a bot,
 * so whether a group may use it is decided here (tbe-essence.groups.require_approval).
 */
class GroupChats
{
    /** Whether a group's updates may reach a handler. Notes a group not seen before. */
    public function mayUse(Chat $chat): bool
    {
        if (! config()->boolean('tbe-essence.groups.require_approval', true)) {
            return true;
        }

        $botChat = BotChat::where('chat_id', (int) $chat->id)->first();

        if ($botChat === null) {
            // The bot was added before groups were on, or the join update was lost.
            $this->askOwner($this->record($chat, 'member'));

            return false;
        }

        return $botChat->isApproved();
    }

    public function recordMembership(ChatMemberUpdated $update): void
    {
        $status = (string) $update->newChatMember->status;
        $botChat = $this->record($update->chat, $status);

        if (! BotChat::isPresentStatus($status)) {
            // Coming back later asks again: whoever approved it knew a group
            // as it was, and it may have changed hands.
            $botChat->forceFill(['approved_at' => null])->save();
            Event::dispatch(new BotChatLeft($botChat));

            return;
        }

        Event::dispatch(new BotChatJoined($botChat));

        if (! $botChat->isApproved() && config()->boolean('tbe-essence.groups.require_approval', true)) {
            $this->askOwner($botChat);
        }
    }

    /**
     * A group became a supergroup, which gives it a new id. Telegram sends one
     * message from each chat, in no promised order, with traffic possibly in
     * between, so this is idempotent and merges into a row that already exists.
     */
    public function migrate(int $oldChatId, int $newChatId): void
    {
        DB::transaction(function () use ($oldChatId, $newChatId) {
            $old = BotChat::where('chat_id', $oldChatId)->first();

            if ($old !== null) {
                $new = BotChat::where('chat_id', $newChatId)->first();

                if ($new === null) {
                    $old->forceFill(['chat_id' => $newChatId, 'type' => 'supergroup'])->save();
                } else {
                    $new->forceFill(['approved_at' => $new->approved_at ?? $old->approved_at, 'type' => 'supergroup'])->save();
                    $old->delete();
                }
            }

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

    private function record(Chat $chat, string $status): BotChat
    {
        return BotChat::updateOrCreate(
            ['chat_id' => (int) $chat->id],
            [
                'type' => (string) $chat->type,
                'title' => $chat->title,
                'username' => $chat->username,
                'bot_status' => $status,
            ],
        );
    }

    private function askOwner(BotChat $botChat): void
    {
        ownerAlert(
            'group-chats.pending.'.$botChat->chat_id,
            fn () => __('tbe::group_chats.alerts.pending', ['title' => $botChat->title ?? (string) $botChat->chat_id]),
        );
    }
}
