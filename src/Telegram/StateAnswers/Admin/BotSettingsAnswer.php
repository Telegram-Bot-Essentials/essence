<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\Features\BotSettingsFeature;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotSettingsAnswer extends StateAnswer
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(string $method): void
    {
        switch (strtolower($method)) {
            case "change_payment_card_number":
                $this->changePaymentCardNumber();
                break;
            case "change_payment_card_name":
                $this->changePaymentCardName();
                break;
            case 'change_transactions_chat_id':
                $this->changeTransactionsChatId();
                break;

            case 'change_zibal_merchant':
                $this->changeZibalMerchant();
                break;

            case 'change_zarinpal_merchant':
                $this->changeZarinpalMerchant();
                break;
        }
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function changePaymentCardNumber(): void
    {
        wHook()->bot()->settings->pay_to_card_number = wHook()->update()->message->text;
        wHook()->bot()->settings->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::toCard()->send();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function sendValueUpdatedMessage(): void
    {
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::general.messages.valueUpdatedSuccessfully'),
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    private function changePaymentCardName(): void
    {
        wHook()->bot()->settings->pay_to_card_name = wHook()->update()->message->text;
        wHook()->bot()->settings->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::toCard()->send();
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function changeTransactionsChatId(): void
    {
        wHook()->bot()->settings->transactions_chat_id = wHook()->update()->message->text;
        wHook()->bot()->settings->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::toCard()->send();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function changeZibalMerchant(): void
    {
        wHook()->bot()->settings->zibal_merchant = wHook()->update()->message->text;
        wHook()->bot()->settings->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        $this->messageMeta()->updateAndContinueAction(BotSettingsFeature::zibal());
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    private function changeZarinpalMerchant(): void
    {
        wHook()->bot()->settings->zarinpal_merchant_id = wHook()->update()->message->text;
        wHook()->bot()->settings->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        $this->messageMeta()->updateAndContinueAction(BotSettingsFeature::zarinpal());
    }
}
