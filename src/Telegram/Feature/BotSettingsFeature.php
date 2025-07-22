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
                'text' => __('tbe::bot_settings.main.keys.manageGateways'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function gateways(): TelegramResponse
    {
        $text = __('tbe::bot_settings.gateways.text.information');

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.toCard', [
                    'status' => wHook()->bot()->settings->pay_with_card ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')
                ]),
                'callback_data' => encodeCallback(self::$type, ['to_card'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.zibal', [
                    'status' => wHook()->bot()->settings->zibal ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')
                ]),
                'callback_data' => encodeCallback(self::$type, ['zibal'])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.zarinpal', [
                    'status' => wHook()->bot()->settings->zarinpal ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')
                ]),
                'callback_data' => encodeCallback(self::$type, ['zarinpal'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.idpay', [
                    'status' => '🚫'
                ]),
                'callback_data' => encodeCallback(self::$type, ['idpay'])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.nextpay', [
                    'status' => '🚫'
                ]),
                'callback_data' => encodeCallback(self::$type, ['nextpay'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.nowpayments', [
                    'status' => '🚫'
                ]),
                'callback_data' => encodeCallback(self::$type, ['nowpayments'])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.zirgozar', [
                    'status' => wHook()->bot()->settings->zirgozar ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')
                ]),
                'callback_data' => encodeCallback(self::$type, ['zirgozar'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.gateways.keys.wallet', [
                    'status' => wHook()->bot()->settings->wallet ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')
                ]),
                'callback_data' => encodeCallback(self::$type, ['wallet'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['start'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function zibal(): TelegramResponse
    {
        $text = __('tbe::bot_settings.zibal.text.information', [
            'activationStatus' => wHook()->bot()->settings->zibal ? __('tbe::general.status.activated') : __('tbe::general.status.deactivated'),
            'zibalMerchant' => wHook()->bot()->settings->zibal_merchant ?? __('tbe::general.status.notSet')
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zibal.keys.activation', [
                    'statusEmoji' => wHook()->bot()->settings->zibal ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'),
                ]),
                'callback_data' => encodeCallback(self::$type, ['switch_zibal_status', intval(!wHook()->bot()->settings->zibal)])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zibal.keys.merchant'),
                'callback_data' => encodeCallback(self::$type, ['change_zibal_merchant'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function toCard(): TelegramResponse
    {
        $text = __('tbe::bot_settings.to_card.text.information', [
            'activationStatus' => (wHook()->bot()->settings->pay_with_card ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'transactionsChatId' => wHook()->bot()->settings->transactions_chat_id ?? __('tbe::general.status.notSet'),
            'paymentCardNumber' => wHook()->bot()->settings->pay_to_card_number ?? __('tbe::general.status.notSet'),
            'paymentCardName' => wHook()->bot()->settings->pay_to_card_name ?? __('tbe::general.status.notSet'),

        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.to_card.keys.payWithCardStatus', [
                    'statusEmoji' => (wHook()->bot()->settings->pay_with_card ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['pay_with_card_status', intval(!wHook()->bot()->settings->pay_with_card)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.to_card.keys.transactionsChatId'),
                'callback_data' => encodeCallback(self::$type, ['change_transactions_chat_id'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.to_card.keys.paymentCardNumber'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_number'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.to_card.keys.paymentCardName'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_name'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function wallet(): TelegramResponse
    {
        $text = __('tbe::bot_settings.wallet.text.information', [
            'activationStatus' => (wHook()->bot()->settings->wallet ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'botCurrency' => wHook()->bot()->currency
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.wallet.keys.activation', [
                    'statusEmoji' => (wHook()->bot()->settings->wallet ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['wallet_status', intval(!wHook()->bot()->settings->wallet)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function zirgozar(): TelegramResponse
    {
        $text = __('tbe::bot_settings.zirgozar.text.information', [
            'activationStatus' => (wHook()->bot()->settings->zirgozar ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'zirgozarToken' => wHook()->bot()->settings->zirgozar_token ?? __('tbe::general.status.notSet'),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zirgozar.keys.activation', [
                    'statusEmoji' => (wHook()->bot()->settings->zirgozar ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['zirgozar_status', intval(!wHook()->bot()->settings->zirgozar)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zirgozar.keys.token'),
                'callback_data' => encodeCallback(self::$type, ['change_zirgozar_token'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function zarinpal(): TelegramResponse
    {
        $text = __('tbe::bot_settings.zarinpal.text.information', [
            'activationStatus' => (wHook()->bot()->settings->zarinpal ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji')),
            'merchantID' => wHook()->bot()->settings->zarinpal_merchant_id ?? __('tbe::general.status.notSet'),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zarinpal.keys.activation', [
                    'statusEmoji' => (wHook()->bot()->settings->zarinpal ? __('tbe::general.status.enabledEmoji') : __('tbe::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['zarinpal_status', intval(!wHook()->bot()->settings->zarinpal)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_settings.zarinpal.keys.merchantID'),
                'callback_data' => encodeCallback(self::$type, ['change_zarinpal_token'])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['gateways'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
