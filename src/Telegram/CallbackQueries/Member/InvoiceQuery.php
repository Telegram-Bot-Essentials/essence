<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\FeatureIsDisabled;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class InvoiceQuery extends CallbackQuery
{
    protected string $type = 'INVOICE';
    protected int $perm = Roles::MEMBER->value;

    /**
     * @param array $params
     * @throws BindingResolutionException
     * @throws FeatureIsDisabled
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "to_card":
                dependsOn(wHook()->bot()->settings->pay_with_card);
                dependsOn(isset(wHook()->bot()->settings->pay_to_card_number));
                dependsOn(isset(wHook()->bot()->settings->pay_to_card_name));
                dependsOn(isset(wHook()->bot()->settings->transactions_chat_id));
                $this->toCard();
                break;
            case "by_wallet":
                $this->byWallet();
                break;
        }
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function toCard(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);

        $paymentAttempt = $invoice->paymentAttempt()->create();
        $paymentAttempt->toCardAttempt()->create([
            'card_number' => wHook()->bot()->settings->pay_to_card_number,
            'amount' => $invoice->price
        ]);
        $invoice->paymentAttempt()->whereNot('id', $paymentAttempt->id)->delete();

        $text = __('tbe::invoice.to_card.text.user-pay_message', [
            'cardNumber' => wHook()->bot()->settings->pay_to_card_number,
            'cardName' => wHook()->bot()->settings->pay_to_card_name
        ]);

        wHook()->user()->changeState(encodeAnswerState($this->type, "pay_to_card", ["invoice_id" => $invoice->id]));

        $invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-waiting_for_payment'));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        $this->answer(__('tbe::invoice.to_card.answers.attempting'));
    }

    private function byWallet(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);
        if($invoice->botUser->balance < $invoice->price){
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe::invoice.by_wallet.answers.creditIsNotEnough', [
                    'credit' => priceFormat($invoice->botUser->balance),
                    'neededCredit' => priceFormat($invoice->price)
                ]),
                'show_alert' => true,
            ]);
            return;
        }

        $paymentAttempt = $invoice->paymentAttempt()->create();
        $byWalletAttempt = $paymentAttempt->byWalletAttempt()->firstOrCreate([
            'amount' => $paymentAttempt->invoice->price
        ]);

        $byWalletAttempt->attempt();
        $invoice->triggerInvoicePaidHook();
        $invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
    }
}
