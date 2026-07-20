<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;

class TelegramPaginator
{
    public static function getPage(string $target, int $currentPage, int $lastPage): int
    {
        return match ($target) {
            'first' => 1,
            'prev' => $currentPage > 1 ? $currentPage - 1 : $lastPage,
            'next' => $currentPage < $lastPage ? $currentPage + 1 : 1,
            'last' => $lastPage,
            default => max(1, min($currentPage, $lastPage)),
        };
    }

    public static function validatePageInput(string|int $page, int $lastPage): void
    {
        Validator::validate(
            ['page' => $page],
            ['page' => "required|integer|min:1|max:$lastPage"]
        );
    }

    public static function makeNavigationButtonsRow(string $callbackType, int $page, int $lastPage, $callbackMethod = 'start', $customPageMethod = 'set_start_page', array $extraParams = [], bool $showFirstLast = true): array
    {
        $prevPage = $page > 1 ? $page - 1 : $lastPage;
        $nextPage = $page < $lastPage ? $page + 1 : 1;

        $buttons = [];

        if ($showFirstLast) {
            $buttons[] = Keyboard::inlineButton(['text' => '<<', 'callback_data' => encodeCallback($callbackType, $callbackMethod, array_merge([1, $page], $extraParams))]);
        }

        $buttons[] = Keyboard::inlineButton(['text' => '<', 'callback_data' => encodeCallback($callbackType, $callbackMethod, array_merge([$prevPage, $page], $extraParams))]);
        $buttons[] = Keyboard::inlineButton(['text' => "$page/{$lastPage}", 'callback_data' => encodeCallback($callbackType, $customPageMethod, $extraParams)]);
        $buttons[] = Keyboard::inlineButton(['text' => '>', 'callback_data' => encodeCallback($callbackType, $callbackMethod, array_merge([$nextPage, $page], $extraParams))]);

        if ($showFirstLast) {
            $buttons[] = Keyboard::inlineButton(['text' => '>>', 'callback_data' => encodeCallback($callbackType, $callbackMethod, array_merge([$lastPage, $page], $extraParams))]);
        }

        return $buttons;
    }

    /**
     * @throws InvalidPageNumber
     */
    public static function validatePageNumber(int $targetPage, int $currentPage, LengthAwarePaginator $models): void
    {
        if ($targetPage == $currentPage)
            throw new InvalidPageNumber(__('tbe::general.alerts.samePageNumber'));
        if ($targetPage < 1 || $targetPage > $models->lastPage())
            throw new InvalidPageNumber(__('tbe::general.alerts.outOfBoundPageNumber'));
    }
}
