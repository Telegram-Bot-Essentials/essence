<?php

namespace TelegramBotEssentials\Essence\Telegram\Features;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class BotAdminsFeature
{
    static string $type = 'BOTADMNS';

    /**
     * @return TelegramResponse
     */
    public static function menu(): TelegramResponse
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
                'callback_data' => encodeCallback(self::$type, ['add_admin'])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_admins.main.keys.owner', ['ownerName' => wHook()->bot()->botOwner->full_name]),
                'callback_data' => encodeCallback(self::$type, ['owner_info'])
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

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
