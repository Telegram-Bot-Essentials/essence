<?php

namespace TelegramBotEssentials\Essence\Telegram\Features\Groups;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Models\BotChat;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class GroupChatsFeature
{
    public const TYPE = 'GROUPCHATS';

    /** Telegram caps a keyboard's size; a bot in more groups than this is not the case to design for. */
    private const LIMIT = 50;

    public static function index(): TelegramResponse
    {
        $chats = BotChat::query()->present()->orderByRaw('approved_at IS NOT NULL')->latest()->limit(self::LIMIT)->get();

        if ($chats->isEmpty()) {
            return new TelegramResponse(text: __('tbe::group_chats.index.empty'));
        }

        $replyMarkup = Keyboard::make()->inline();

        foreach ($chats as $chat) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __($chat->isApproved() ? 'tbe::group_chats.index.approved' : 'tbe::group_chats.index.pending', [
                        'title' => $chat->title ?? (string) $chat->chat_id,
                    ]),
                    'callback_data' => encodeCallback(self::TYPE, $chat->isApproved() ? 'revoke' : 'approve', [$chat->id]),
                ]),
            ]);
        }

        return new TelegramResponse(text: __('tbe::group_chats.index.text'), replyMarkup: $replyMarkup);
    }
}
