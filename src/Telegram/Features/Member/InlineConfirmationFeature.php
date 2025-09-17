<?php

namespace TelegramBotEssentials\Essence\Telegram\Features\Member;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class InlineConfirmationFeature
{
    static string $type = 'INLINECONFIRMATION';

    public static function load(InlineConfirmation $inlineConfirmation): TelegramResponse
    {
        $text = $inlineConfirmation->confirmation_text ?? "Do you confirm this action?";

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "Accept",
                'callback_data' => encodeCallback(self::$type, ['accept', $inlineConfirmation->id])
            ]),
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
