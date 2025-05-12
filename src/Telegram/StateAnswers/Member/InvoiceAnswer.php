<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Member;

use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class InvoiceAnswer extends StateAnswer
{
    protected string $type = 'INVOICE';
    protected int $perm = Roles::MEMBER->value;
    protected array $allowedFields = [AllowableFields::TEXT->value, AllowableFields::PHOTO->value];

    /**
     * @param string $method
     * @param array $params
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    public function handle(string $method, array $params): void
    {
        $this->params = $params;
        switch (strtolower($method)) {
            case "pay_to_card":
                $this->payToCard();
                break;
            case "cancel":
                $this->cancel();
                break;
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    private function payToCard(): void
    {
        $invoice = Invoice::findOrFail($this->params['invoice_id']);
        $this->storePaymentInformation($invoice);

        $invoice->paymentAttempt->toCardAttempt->received_at = now();
        $invoice->paymentAttempt->toCardAttempt->save();

        $invoice->messageMeta->lockAction(__('invoice.to_card.lock-keys.user-wait_for_payment_processing'));
        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->update()->message->from->id,
            'text' => __('invoice.to_card.text.user-payment_result'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $text = __('invoice.to_card.text.admin-payment_result', [
            'invoiceId' => $invoice->id,
            'invoiceDescription' => $invoice->payable->description ?? null,
            'paymentDescription' => wHook()->update()->message?->photo ? wHook()->update()->message->caption : wHook()->update()->message->text,
        ]);

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([Keyboard::inlineButton([
            'text' => __('invoice.to_card.keys.admin-accept_payment'),
            'callback_data' => encodeCallback('MANAGE_INVOICE', ['accept_card_payment', $invoice->paymentAttempt->toCardAttempt->id])
        ]), Keyboard::inlineButton([
            'text' => __('invoice.to_card.keys.admin-reject_payment'),
            'callback_data' => encodeCallback('MANAGE_INVOICE', ['reject_card_payment', $invoice->paymentAttempt->toCardAttempt->id])
        ])]);


        if (wHook()->update()->message?->text) {
            $message = wHook()->api()->sendMessage([
                'chat_id' => wHook()->bot()->settings->transactions_chat_id,
                'text' => $text,
                'reply_markup' => $replyMarkup,
            ]);
        } else {
            $message = wHook()->api()->copyMessage([
                'chat_id' => wHook()->bot()->settings->transactions_chat_id,
                'from_chat_id' => wHook()->update()->message->from->id,
                'message_id' => wHook()->update()->message->messageId,
                'caption' => $text,
                'reply_markup' => $replyMarkup,
            ]);
        }

        $invoice->paymentAttempt->toCardAttempt->messageMeta
            ->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
    }

    /**
     * @throws TelegramSDKException
     */
    private function storePaymentInformation(Invoice $invoice): void
    {
        $toCardAttempt = $invoice->paymentAttempt->toCardAttempt;
        $toCardAttempt->info_text = wHook()->update()->message?->photo ? wHook()->update()->message->caption : wHook()->update()->message->text;

        if (wHook()->update()->message?->photo[0] ?? null) {
            $path = Storage::disk()->path(time() . '.jpg');
            wHook()->api()->downloadFile(wHook()->update()->message->photo[0], $path);
            $base64EncodedPhoto = base64_encode(file_get_contents($path));
            Storage::delete($path);
            $toCardAttempt->info_photo = $base64EncodedPhoto;
        }

        $toCardAttempt->save();
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        $invoice = Invoice::findOrFail($this->params['invoice_id']);
        $invoice->paymentAttempt()->delete();
        $invoice->messageMeta->continueAction();
    }
}
