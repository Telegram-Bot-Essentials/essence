<?php

namespace TelegramBotEssentials\Essence\Telegram\Features\Member;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class InlineConfirmationFeature
{
    public static string $type = 'INLINECONFIRMATION';

    public static function load(InlineConfirmation $inlineConfirmation): TelegramResponse
    {
        $text = $inlineConfirmation->confirmation_text ?? __('tbe::inline_confirmation.main.text.confirmationQuestion');

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::inline_confirmation.main.keys.accept'),
                'callback_data' => encodeCallback(self::$type, 'accept', [$inlineConfirmation->id]),
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::inline_confirmation.main.keys.decline'),
                'callback_data' => encodeCallback(self::$type, 'decline', [$inlineConfirmation->id]),
            ]),
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML',
        );
    }
}
