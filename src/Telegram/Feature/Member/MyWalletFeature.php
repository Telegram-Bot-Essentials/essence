<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature\Member;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class MyWalletFeature
{
    static string $type = 'MYWALLET';

    public static function main(): TelegramResponse
    {
        $text = "Total Credit: " . priceFormat(wHook()->user()->balance);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "Add credit 💲",
                'callback_data' => encodeCallback(self::$type, ['add_credit'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
