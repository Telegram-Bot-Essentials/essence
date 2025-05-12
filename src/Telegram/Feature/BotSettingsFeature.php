<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Models\BotSettings;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class BotSettingsFeature
{
    static string $type = 'BTSTNG';

    /**
     * @return void
     * @throws TelegramSDKException
     */
    public static function menuSend(): void
    {
        $data = self::menuRaw();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $data['text'],
            'reply_markup' => $data['reply_markup'],
            'parse_mode' => 'HTML',
        ]);

    }

    /**
     * @return array
     */
    private static function menuRaw(): array
    {
        $text = __('telegram-bot-essentials::bot_settings.main.text.information', [
            'botStatus' => (wHook()->bot()->settings->bot_status ? __('telegram-bot-essentials::general.status.enabledEmoji') : __('telegram-bot-essentials::general.status.disabledEmoji')),
            'payWithCardStatus' => (wHook()->bot()->settings->pay_with_card ? __('telegram-bot-essentials::general.status.enabledEmoji') : __('telegram-bot-essentials::general.status.disabledEmoji')),
            'transactionsChatId' => wHook()->bot()->settings->transactions_chat_id ?? __('telegram-bot-essentials::general.status.notSet'),
            'paymentCardNumber' => wHook()->bot()->settings->pay_to_card_number ?? __('telegram-bot-essentials::general.status.notSet'),
            'paymentCardName' => wHook()->bot()->settings->pay_to_card_name ?? __('telegram-bot-essentials::general.status.notSet'),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.main.keys.botStatus', [
                    'status' => (wHook()->bot()->settings->bot_status ? __('telegram-bot-essentials::general.status.enabledEmoji') : __('telegram-bot-essentials::general.status.disabledEmoji'))
                    ]),
                'callback_data' => encodeCallback(self::$type, ['bot_status', intval(!wHook()->bot()->settings->bot_status)])
            ]),
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.main.keys.payWithCardStatus', [
                    'status' => (wHook()->bot()->settings->pay_with_card ? __('telegram-bot-essentials::general.status.enabledEmoji') : __('telegram-bot-essentials::general.status.disabledEmoji'))
                ]),
                'callback_data' => encodeCallback(self::$type, ['pay_with_card_status', intval(!wHook()->bot()->settings->pay_with_card)])
            ])
        ]);


//        $replyMarkup->row([
//            Keyboard::inlineButton([
//                'text' => __('telegram-bot-essentials::bot_settings.keysSeparatorPlaceHolder'),
//                'callback_data' => encodeCallback('place_holder')
//            ])
//        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.main.keys.transactionsChatId'),
                'callback_data' => encodeCallback(self::$type, ['change_transactions_chat_id'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.main.keys.paymentCardNumber'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_number'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.main.keys.paymentCardName'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_name'])
            ])
        ]);

        return ['reply_markup' => $replyMarkup, 'text' => $text];
    }

    /**
     * @return void
     * @throws TelegramSDKException
     */
    public static function menuEdit(): void
    {
        $data = self::menuRaw();

        wHook()->api()->editMessageText([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId,
            'text' => $data['text'],
            'parse_mode' => 'HTML',
            'reply_markup' => $data['reply_markup']
        ]);
    }
}
