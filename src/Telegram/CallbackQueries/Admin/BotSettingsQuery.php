<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Models\BotSettings;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;

class BotSettingsQuery extends CallbackQuery
{
    protected string $type = 'BTSTNG';
    protected int $perm = 100;

    /**
     * @param array $params
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case 'bot_status':
                $this->botStatus();
                break;
            case "pay_with_card_status":
                $this->payWithCardStatus();
                break;

            case "change_payment_card_number":
                $this->changePaymentCardNumber();
                break;
            case "change_payment_card_name":
                $this->changePaymentCardName();
                break;
            case "change_transactions_chat_id":
                $this->changeTransactionsChatId();
                break;
        }
    }

    private function botStatus(): void
    {
        BotSettings::forCurrentBot()->first()->bot_status = $this->params[1];
        BotSettings::forCurrentBot()->first()->bot_status->save();
        BotSettingsFeature::menuEdit();
        $this->answer("Bot Status " . ($this->params[1] ? 'enabled' : 'disabled'));
    }

    private function buyServiceStatus(): void
    {
        BotSettings::forCurrentBot()->first()->buy_service = $this->params[1];
        BotSettings::forCurrentBot()->first()->save();
        BotSettingsFeature::menuEdit();
        $this->answer("Buy Service " . ($this->params[1] ? 'enabled' : 'disabled'));
    }

    private function payWithCardStatus(): void
    {
        BotSettings::forCurrentBot()->first()->pay_with_card = $this->params[1];
        BotSettings::forCurrentBot()->first()->save();
        BotSettingsFeature::menuEdit();
        $this->answer("Pay with Card " . ($this->params[1] ? 'enabled' : 'disabled'));
    }

    private function changePaymentCardNumber(): void
    {
        $text = __('telegram-bot-essentials::bot_settings.changePaymentCardNumberMessage');

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_payment_card_number"));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        wHook()->api()->deleteMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId
        ]);
    }

    private function changePaymentCardName(): void
    {
        $text = __('telegram-bot-essentials::bot_settings.changePaymentCardNameMessage');

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_payment_card_name"));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        wHook()->api()->deleteMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId
        ]);
        $this->answer("Updating payment card number...");
    }

    private function changeTransactionsChatId(): void
    {
        $text = __('telegram-bot-essentials::bot_settings.transactionsChatIdMessage');

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_transactions_chat_id"));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        wHook()->api()->deleteMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId
        ]);
        $this->answer("Updating transactions chat id...");
    }

    private function changeConnectionGuideChanngelId(): void
    {
        $text = __('telegram-bot-essentials::bot_settings.connectionGuideChannelIdMessage');

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_connection_guide_channel_id"));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        wHook()->api()->deleteMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId
        ]);
        $this->answer("Updating connection guide channel id...");
    }
}
