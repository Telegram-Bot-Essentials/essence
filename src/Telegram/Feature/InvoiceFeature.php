<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Models\CreditOrder;
use Elyar\TelegramBotEssentials\Models\Billing\Invoice;
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

        if(wHook()->bot()->settings->pay_with_card) {
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.to_card', [
                    'price' => number_format(priceIn($invoice->price)->toIRT())
                ]),
                'callback_data' => encodeCallback(self::$type, ['to_card', $invoice->id])
            ])]);
        }

        if(wHook()->bot()->settings->zirgozar){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.to_zirgozar', [
                    'price' => number_format(priceIn($invoice->price)->toIRT())
                ]),
                'url' => route('invoice.zirgozar.pay', ['token' => $invoice->public_token])
            ])]);
        }

        if(wHook()->bot()->settings->zibal){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.to_zibal', [
                    'price' => number_format(priceIn($invoice->price)->toIRT())
                ]),
                'url' => route('invoice.zibal.pay', ['token' => $invoice->public_token])
            ])]);
        }

        if(wHook()->bot()->settings->zarinpal){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.to_zarinpal', [
                    'price' => number_format(priceIn($invoice->price)->toIRT())
                ]),
                'url' => route('invoice.zarinpal.pay', ['token' => $invoice->public_token])
            ])]);
        }

        if(!($invoice->payable instanceof CreditOrder) && wHook()->bot()->settings->wallet){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.by_wallet', [
                    'price' => currency()->priceFormat($invoice->price)
                ]),
                'callback_data' => encodeCallback(self::$type, ['by_wallet', $invoice->id])
            ])]);
        }

        if($encodedCallback){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe::invoice.summary.keys.back_to_previous'),
                'callback_data' => $encodedCallback
            ])]);
        }

        return (new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            answer: $invoice->wasRecentlyCreated ?
                __('tbe::invoice.summary.answers.created') :
                __('tbe::invoice.summary.answers.main')
        ))->messageMetaModel($invoice, 'invoice_view');
    }
}
