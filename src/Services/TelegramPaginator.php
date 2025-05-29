<?php

namespace Elyar\TelegramBotEssentials\Services;

use Elyar\TelegramBotEssentials\Exceptions\InvalidPageNumber;
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

    public static function isOutOfBounds(string $target, int $currentPage, int $lastPage): ?string
    {
        return match (true) {
            ($target === 'first' || $target === 'prev') && $currentPage <= 1 => 'Already on first page',
            ($target === 'last' || $target === 'next') && $currentPage >= $lastPage => 'Already on last page',
            default => null,
        };
    }

    public static function validatePageInput(string|int $page, int $lastPage): void
    {
        Validator::validate(
            ['page' => $page],
            ['page' => "required|integer|min:1|max:$lastPage"]
        );
    }

    public static function makeNavigationButtonsRow(string $callbackType, int $page, int $lastPage): array
    {
        return [
            Keyboard::inlineButton(['text' => '<<', 'callback_data' => encodeCallback($callbackType, ['start', $page, 'first'])]),
            Keyboard::inlineButton(['text' => '<', 'callback_data' => encodeCallback($callbackType, ['start', $page, 'prev'])]),
            Keyboard::inlineButton(['text' => "$page/$lastPage", 'callback_data' => encodeCallback($callbackType, ['start_with_page', $page])]),
            Keyboard::inlineButton(['text' => '>', 'callback_data' => encodeCallback($callbackType, ['start', $page, 'next'])]),
            Keyboard::inlineButton(['text' => '>>', 'callback_data' => encodeCallback($callbackType, ['start', $page, 'last'])]),
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
