<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\BotSettings;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;

class BotSettingsAnswer extends StateAnswer
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @param array $params
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

    private function changePaymentCardNumber(): void
    {
        BotSettings::forCurrentBot()->first()->pay_to_card_number = wHook()->update()->message->text;
        BotSettings::forCurrentBot()->first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menuSend();
    }

    private function changePaymentCardName(): void
    {
        BotSettings::forCurrentBot()->first()->pay_to_card_name = wHook()->update()->message->text;
        BotSettings::forCurrentBot()->first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menuSend();
    }

    private function changeTransactionsChatId(): void
    {
        BotSettings::forCurrentBot()->first()->transactions_chat_id = wHook()->update()->message->text;
        BotSettings::forCurrentBot()->first()->save();

        wHook()->user()->changeState();
        $this->sendValueUpdatedMessage();
        BotSettingsFeature::menuSend();
    }

    function cancel(): void
    {
        // TODO: Implement cancel() method.
    }

    private function sendValueUpdatedMessage(): void
    {
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Value updated successfully",
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
