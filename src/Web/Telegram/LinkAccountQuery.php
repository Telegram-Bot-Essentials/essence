<?php

namespace TelegramBotEssentials\Essence\Web\Telegram;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Web\LinkCodes;
use TelegramBotEssentials\Essence\Web\TelegramAccountTaken;

/**
 * The Telegram account being added confirms or refuses the link. Only a
 * press here links it: opening a link someone sent is not enough, since
 * whoever holds the web account then acts as this Telegram account.
 */
class LinkAccountQuery extends CallbackQuery
{
    public const TYPE = 'TBE_WEBLINK';

    protected string $type = self::TYPE;

    public static function confirmData(string $code): string
    {
        return encodeCallback(self::TYPE, 'confirm', [$code]);
    }

    public static function cancelData(string $code): string
    {
        return encodeCallback(self::TYPE, 'cancel', [$code]);
    }

    public function confirm(string $code): void
    {
        $this->answer();

        $account = app(LinkCodes::class)->consume($code);
        if ($account === null) {
            $this->say((string) __('tbe-essence::web.link_expired'));

            return;
        }

        try {
            $account->linkTelegram(wHook()->user()->peerId());
        } catch (TelegramAccountTaken) {
            $this->say((string) __('tbe-essence::web.link_taken'));

            return;
        }

        $this->say((string) __('tbe-essence::web.link_done', ['account' => LinkCodes::accountName($account)]));
    }

    public function cancel(string $code): void
    {
        $this->answer();
        app(LinkCodes::class)->consume($code);
        $this->say((string) __('tbe-essence::web.link_cancelled'));
    }

    /** In place of the question, so its buttons cannot be pressed again. */
    private function say(string $text): void
    {
        $message = wHook()->update()->callbackQuery?->message;

        try {
            if ($message !== null) {
                wHook()->api()->editMessageText([
                    'chat_id' => $message->chat->id,
                    'message_id' => $message->messageId,
                    'text' => $text,
                ]);

                return;
            }
        } catch (TelegramSDKException) {
            // Too old to edit: say it in a new message.
        }

        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => $text]);
    }
}
