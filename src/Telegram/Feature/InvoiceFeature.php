<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Services\CurrencyFather;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class InvoiceFeature
{
    static string $type = 'INVOICE';

    public static function invoice(Invoice $invoice, ?string $encodedCallback = null): TelegramResponse
    {
        $text = __('tbe::invoice.summary.text.information', [
            'invoiceId' => $invoice->id,
            'orderDescription' => $invoice->payable->description ?? null
        ]);

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('tbe::invoice.summary.keys.to_card', [
                'price' => number_format(priceIn($invoice->price)->toIRT())
            ]),
            'callback_data' => encodeCallback(self::$type, ['to_card', $invoice->id])
        ])]);

        // TODO: Add payment option by wallet if it is not Credit Order

        if($encodedCallback){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.back_to_previous'),
                'callback_data' => $encodedCallback
            ])]);
        }

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            answer: $invoice->wasRecentlyCreated ?
                __('tbe::invoice.summary.answers.created') :
                __('tbe::invoice.summary.answers.main')
        );
    }
}
