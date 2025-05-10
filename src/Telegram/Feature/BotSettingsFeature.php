<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Models\BotSettings;
use Telegram\Bot\Keyboard\Keyboard;

class BotSettingsFeature
{
    static string $type = 'BTSTNG';

    public static function menuSend(): void
    {
        $data = self::menuRaw();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $data['text'],
            'reply_markup' => $data['reply_markup'],
        ]);

    }

    private static function menuRaw(): array
    {
        $text = __('telegram-bot-essentials::bot_settings.settingsText', [
            'botStatus' => (wHook()->bot()->settings->bot_status ? __('telegram-bot-essentials::general.enabled') : __('telegram-bot-essentials::general.disabled')),
            'payWithCardStatus' => (wHook()->bot()->settings->pay_with_card ? __('telegram-bot-essentials::general.enabled') : __('telegram-bot-essentials::general.disabled')),
            'transactionsChatId' => wHook()->bot()->settings->transactions_chat_id ?? __('telegram-bot-essentials::general.notSetString'),
            'paymentCardNumber' => wHook()->bot()->settings->pay_to_card_number ?? __('telegram-bot-essentials::general.notSetString'),
            'paymentCardName' => wHook()->bot()->settings->pay_to_card_name ?? __('telegram-bot-essentials::general.notSetString'),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.botStatusSwitch') . (wHook()->bot()->settings->bot_status ? __('telegram-bot-essentials::general.enabled') : __('telegram-bot-essentials::general.disabled')),
                'callback_data' => encodeCallback(self::$type, ['bot_status', intval(!wHook()->bot()->settings->bot_status)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.payWithCardSwitch') . (wHook()->bot()->settings->pay_with_card ? __('telegram-bot-essentials::general.enabled') : __('telegram-bot-essentials::general.disabled')),
                'callback_data' => encodeCallback(self::$type, ['pay_with_card_status', intval(!wHook()->bot()->settings->pay_with_card)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.keysSeparatorPlaceHolder'),
                'callback_data' => encodeCallback('place_holder')
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.transactionsChat'),
                'callback_data' => encodeCallback(self::$type, ['change_transactions_chat_id'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.paymentCardNumber'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_number'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('telegram-bot-essentials::bot_settings.paymentCardName'),
                'callback_data' => encodeCallback(self::$type, ['change_payment_card_name'])
            ])
        ]);

        return ['reply_markup' => $replyMarkup, 'text' => $text];
    }

    public static function menuEdit(): void
    {
        $data = self::menuRaw();

        wHook()->api()->editMessageText([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId,
            'text' => $data['text'],
            'reply_markup' => $data['reply_markup']
        ]);
    }
}
