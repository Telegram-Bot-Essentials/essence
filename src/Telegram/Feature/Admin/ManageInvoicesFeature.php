<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature\Admin;
use Elyar\TelegramBotEssentials\Exceptions\InvalidPageNumber;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class ManageInvoicesFeature
{
    static string $type = 'MANAGEINVOICES';

    // TODO: Implement static functions for generating bot messages

    /**
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $text = 'invoices';

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoices = Invoice::paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $invoices);

        if(count($invoices) == 0){
            $text = 'No invoices found';
            return new TelegramResponse(
                text: $text,
                parseMode: 'HTML'
            );
        }

        foreach ($invoices as $invoice) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => $invoice->id,
                    'callback_data' => encodeCallback(self::$type, ['show', $invoice->id, $page])
                ])
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $invoices->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function show(Invoice $invoice, int $lastPage = 1): TelegramResponse
    {
        $text = __('tbe::manage_invoices.main.text.show', [
            'invoiceId' => $invoice->id,
            'invoiceOwner' => "<a href=\"tg://user?id={$invoice->botUser->telegramUser->peer_id}\">{$invoice->botUser->telegramUser->full_name}</a>",
            'invoiceAmount' => priceFormat($invoice->price),
            'invoiceStatus' => $invoice->status,
            'paymentAttempt' => $invoice->paymentAttempt?->id,
            'paymentAttemptStatus' => $invoice->status,
            'paymentAttemptDate' => $invoice->paymentAttempt?->created_at,
            'orderDescription' => $invoice->paymentAttempt?->description,
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        if($invoice->status != 'pending') {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => 'Mark as pending',
                    'callback_data' => encodeCallback(self::$type, ['mark_as_pending', $invoice->id, $lastPage])
                ])
            ]);
        }

        if($invoice->status != 'paid') {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => 'Mark as Paid',
                    'callback_data' => encodeCallback(self::$type, ['mark_as_paid', $invoice->id, $lastPage])
                ])
            ]);
        }

        if($invoice->status != 'failed') {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => 'Mark as failed',
                    'callback_data' => encodeCallback(self::$type, ['mark_as_failed', $invoice->id, $lastPage])
                ])
            ]);
        }

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['start', $lastPage, 0])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
