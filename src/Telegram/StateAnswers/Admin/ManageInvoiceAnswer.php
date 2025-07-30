<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\Billing\Attempts\ToCardAttempt;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ManageInvoiceAnswer extends StateAnswer
{
    protected string $type = 'MANAGE_INVOICE';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(string $method): void
    {
        switch (strtolower($method)) {
            case "reject_reason":
                $this->rejectReason();
                break;
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    private function rejectReason(): void
    {
        $toCardAttempt = ToCardAttempt::findOrFail($this->params['to_card_attempt_id']);
        $toCardAttempt->attemptFailed();

        $toCardAttempt->reject_reason = wHook()->update()->message->text;
        $toCardAttempt->save();
        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->update()->message->chat->id,
            'text' => __('tbe::invoice.to_card.text.admin-payment_rejected'),
            'reply_to_message_id' => $toCardAttempt->messageMeta->message_id,
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $text = __('tbe::invoice.to_card.text.user-payment_rejected', [
            'rejectionReason' => $toCardAttempt->reject_reason
        ]);

        wHook()->api()->sendMessage([
            'chat_id' => $toCardAttempt->invoice->botUser->telegramUser->peer_id,
            'text' => $text,
        ]);

        $toCardAttempt->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.admin-payment_rejected_by', [
            'adminName' => wHook()->user()->telegramUser->full_name]), customEmoji: "❌");
        $toCardAttempt->invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-payment_rejected'), '❌');
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        $toCardAttempt = ToCardAttempt::findOrFail($this->params['to_card_attempt_id']);
        $toCardAttempt->messageMeta->revertAction();
    }
}
