<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature\Admin;

use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Telegram\Bot\Keyboard\Keyboard;

class BotUsersFeature
{
    static string $type = 'BOTUSERS';

    // TODO: Implement static functions for generating bot messages
    public static function start(int $page = 1): TelegramResponse
    {
        $text = 'users';
        $users = BotUser::paginate(perPage: 10, page: $page);
        $replyMarkup = Keyboard::make()->inline();

        foreach ($users as $botUser) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => $botUser->telegramUser->first_name . ' ' . $botUser->telegramUser->last_name,
                    'callback_data' => encodeCallback(self::$type, ['show', $botUser->id, $page])
                ])
            ]);
        }
        $replyMarkup->row(
            TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $users->lastPage())
        );

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function show(BotUser $botUser, int $lastPage = 1): TelegramResponse
    {
        $text = $botUser->telegramUser->full_name;

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['start_with', $lastPage])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
