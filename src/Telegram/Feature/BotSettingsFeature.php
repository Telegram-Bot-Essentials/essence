<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class BotSettingsFeature
{
    static string $type = 'BTSTNG';

    /**
     * @return TelegramResponse
     */
    public static function menu(): TelegramResponse
    {
        $text = __('tbe::bot_settings.main.text.information', [
            'botStatus' => (wHook()->bot()->settings->bot_status ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'payWithCardStatus' => (wHook()->bot()->settings->pay_with_card ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'transactionsChatId' => wHook()->bot()->settings->transactions_chat_id ?? __('tbe::general.status.notSet'),
            'paymentCardNumber' => wHook()->bot()->settings->pay_to_card_number ?? __('tbe::general.status.notSet'),
            'paymentCardName' => wHook()->bot()->settings->pay_to_card_name ?? __('tbe::general.status.notSet'),
            'language' => wHook()->bot()->settings->language,
            'defaultCurrency' => wHook()->bot()->currency
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.botStatus', [
                    'status' => (wHook()->bot()->settings->bot_status ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['bot_status', intval(!wHook()->bot()->settings->bot_status)])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.botLanguage', [
                    'language' => wHook()->bot()->settings->language
                ]),
                'callback_data' => encodeCallback(self::$type, ['bot_language'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.payWithCardStatus', [
                    'status' => (wHook()->bot()->settings->pay_with_card ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['pay_with_card_status', intval(!wHook()->bot()->settings->pay_with_card)])
            ])
        ]);

//        $replyMarkup->row([
//            Keyboard::inlineButton([
//                'text' => __('tbe::bot_settings.keysSeparatorPlaceHolder'),
//                'callback_data' => encodeCallback('place_holder')
//            ])
//        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.transactionsChatId'),
                'callback_data' => encodeCallback(self::$type, ['change_transactions_chat_id'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.paymentCardNumber'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_number'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.paymentCardName'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_name'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
