<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\BotSettings;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotSettingsAnswer extends StateAnswer
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @param array $params
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(string $method, array $params): void
    {
        $this->params = $params;
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
        BotSettings::first()->pay_to_card_number = wHook()->update()->message->text;
        BotSettings::first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menu()->send();
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
        BotSettings::first()->pay_to_card_name = wHook()->update()->message->text;
        BotSettings::first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menu()->send();
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function changeTransactionsChatId(): void
    {
        BotSettings::first()->transactions_chat_id = wHook()->update()->message->text;
        BotSettings::first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menu()->send();
    }

    function cancel(): void
    {
        // TODO: Implement cancel() method.
    }
}
