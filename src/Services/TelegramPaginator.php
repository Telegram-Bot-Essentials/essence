<?php

namespace TelegramBotEssentials\Essence\Services;

use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramPaginator
{
    public static function getPage(string $target, int $currentPage, int $lastPage): int
    {
        return match ($target) {
            'first' => 1,
            'prev' => max(1, $currentPage - 1),
            'next' => min($lastPage, $currentPage + 1),
            'last' => $lastPage,
            default => $currentPage,
        };
    }

    public static function validatePageInput(string|int $page, int $lastPage): void
    {
        Validator::validate(
            ['page' => $page],
            ['page' => "required|integer|min:1|max:$lastPage"]
        );
    }

    public static function makeNavigationButtonsRow(string $callbackType, int $page, int $lastPage, $callbackMethod = 'start', $customPageMethod = 'set_start_page'): array
    {
        return [
            Keyboard::inlineButton(['text' => '<<', 'callback_data' => encodeCallback($callbackType, [$callbackMethod, 1, $page])]),
            Keyboard::inlineButton(['text' => '<', 'callback_data' => encodeCallback($callbackType, [$callbackMethod, $page - 1, $page])]),
            Keyboard::inlineButton(['text' => "$page/{$lastPage}", 'callback_data' => encodeCallback($callbackType, [$customPageMethod])]),
            Keyboard::inlineButton(['text' => '>', 'callback_data' => encodeCallback($callbackType, [$callbackMethod, $page + 1, $page])]),
            Keyboard::inlineButton(['text' => '>>', 'callback_data' => encodeCallback($callbackType, [$callbackMethod, $lastPage, $page])]),
        ];
    }

    /**
     * @throws InvalidPageNumber
     */
    public static function validatePageNumber(int $targetPage, int $currentPage, LengthAwarePaginator $models): void
    {
        if($targetPage == $currentPage)
            throw new InvalidPageNumber(__('tbe::general.alerts.samePageNumber'));
        if($targetPage < 1 || $targetPage > $models->lastPage())
            throw new InvalidPageNumber(__('tbe::general.alerts.outOfBoundPageNumber'));
    }
}
