<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature\Member;
use Elyar\TelegramBotEssentials\Models\InlineConfirmation;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class InlineConfirmationFeature
{
    static string $type = 'INLINECONFIRMATION';

    public static function load(InlineConfirmation $inlineConfirmation): TelegramResponse
    {
        $text = "Do you want to confirm this action?";

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "Accept",
                'callback_data' => encodeCallback(self::$type, ['accept', $inlineConfirmation->id])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "Decline",
                'callback_data' => encodeCallback(self::$type, ['decline', $inlineConfirmation->id])
            ])
        ]);

        return (new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
        ));
    }
}
