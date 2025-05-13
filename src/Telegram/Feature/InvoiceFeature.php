<?php

namespace Elyar\TelegramBotEssentials\Telegram\Feature;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class InvoiceFeature
{
    static string $type = 'INVOICE';

    /**
     * @throws TelegramSDKException
     */
    public static function invoiceEdit(Invoice $invoice, string $encodedCallbackOfBackKey): void
    {
        $data = self::invoiceRaw($invoice, $encodedCallbackOfBackKey);
        wHook()->api()->editMessageText([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId,
            'text' => $data['text'],
            'reply_markup' => $data['reply_markup']
        ]);
        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => $data['answer'],
        ]);
    }

    public static function invoiceRaw(Invoice $invoice, string $encodedCallback): array
    {
        $text = __('tbe::invoice.summary.text.information', [
            'invoiceId' => $invoice->id,
            'orderDescription' => $invoice->payable->description ?? null
        ]);

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('tbe::invoice.summary.keys.to_card', [
                'price' => number_format($invoice->price)
            ]),
            'callback_data' => encodeCallback(self::$type, ['to_card', $invoice->id])
        ])])->row([Keyboard::inlineButton([
            'text' => __('tbe::invoice.summary.keys.back_to_previous'),
            'callback_data' => $encodedCallback
        ])]);

        return ['reply_markup' => $replyMarkup,
            'text' => $text,
            'answer' => $invoice->wasRecentlyCreated ?
                __('tbe::invoice.summary.answers.created') :
                __('tbe::invoice.summary.answers.main')];
    }

//    public static function invoiceSend(Invoice $invoice): void
//    {
//        $data = self::invoiceRaw($invoice);
//        wHook()->api()->sendMessage([
//            'chat_id' => wHook()->user()->telegramUser->peer_id,
//            'text' => $data['text'],
//            'reply_markup' => $data['reply_markup']
//        ]);
//    }
}
