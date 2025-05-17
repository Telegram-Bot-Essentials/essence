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
            'defaultCurrency' => wHook()->bot()->settings->default_currency
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('tbe::bot_settings.main.keys.botStatus', [
                'status' => (wHook()->bot()->settings->bot_status ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
            ]),
            'callback_data' => encodeCallback(self::$type, ['bot_status', intval(!wHook()->bot()->settings->bot_status)])
        ])]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.botCurrency', [
                    'defaultCurrency' => wHook()->bot()->settings->default_currency
                ]),
                'callback_data' => encodeCallback(self::$type, ['bot_currency',
                    getNextFromArray(getSupportedCurrencies(), wHook()->bot()->settings->default_currency)
                ])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.botLanguage', [
                    'language' => wHook()->bot()->settings->language
                ]),
                'callback_data' => encodeCallback(self::$type, ['bot_language'])
            ])
        ]);

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('tbe::bot_settings.main.keys.manageCurrencies'),
            'callback_data' => encodeCallback(self::$type, ['bot_supported_currencies'])
        ])]);

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

    public static function supportedCurrencies(): TelegramResponse
    {
        $text = __('tbe::bot_settings.main.text.supported_currencies');

        $replyMarkup = Keyboard::make()
            ->inline();

        $currencies = getSupportedCurrencies();
        $allowedCurrencies = wHook()->bot()->settings->currencies->pluck('name')->toArray();

        $currencyKeys = [];
        foreach ($currencies as $currency) {
            $status = in_array($currency, $allowedCurrencies);
            $currencyKeys[] = Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.main.keys.currencyStatus', [
                    'currency' => $currency,
                    'status' => ($status ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, [
                    'currency_status',
                    $currency,
                    intval(!$status)
                ])
            ]);
        }
        addInlineKeysSorted($replyMarkup, $currencyKeys, 2);

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('tbe::general.keys.back'),
            'callback_data' => encodeCallback(self::$type, ['start'])
        ])]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
