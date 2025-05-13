<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class BotAdminsFeature
{
    static string $type = 'BOTADMNS';

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
            'parse_mode' => $data['parse_mode'],
        ]);

    }

    /**
     * @return array
     */
    public static function menuRaw(): array
    {
        $admins = wHook()->bot()->botUsers()->where('power', '>=', Roles::ADMIN->value)->get();
        $text = __('tbe::bot_admins.main.text.information', [
            'botOwner' => wHook()->bot()->botOwner->full_name,
            'adminCount' => $admins->count(),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_admins.main.keys.addNewAdmin'),
                'callback_data' => encodeCallback(self::$type, ['add_admin', intval(!wHook()->bot()->settings->bot_status)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_admins.main.keys.owner', ['ownerName' => wHook()->bot()->botOwner->full_name]),
                'callback_data' => encodeCallback(self::$type, ['owner_info', intval(!wHook()->bot()->settings->bot_status)])
            ])
        ]);

        foreach ($admins as $admin) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __('tbe::bot_admins.main.keys.removeAdmin', ['adminName' => $admin->telegramUser->full_name]),
                    'callback_data' => encodeCallback(self::$type, ['delete_admin', $admin->id])
                ])
            ]);
        }

        return ['reply_markup' => $replyMarkup, 'text' => $text, 'parse_mode' => 'HTML'];
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
            'parse_mode' => $data['parse_mode'],
            'reply_markup' => $data['reply_markup']
        ]);
    }
}
