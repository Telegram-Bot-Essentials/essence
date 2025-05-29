<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature\Member;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class MyWalletFeature
{
    static string $type = 'MYWALLET';

    // TODO: Implement static functions for generating bot messages
    public static function menu(): TelegramResponse
    {
        $text = 'menu';

        $replyMarkup = Keyboard::make()
            ->inline();

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
