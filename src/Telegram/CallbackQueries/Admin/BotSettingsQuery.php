<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\Currency;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotSettingsQuery extends CallbackQuery
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param array $params
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "start":
                $this->start();
                break;

            case 'bot_status':
                $this->botStatus();
                break;
            case "pay_with_card_status":
                $this->payWithCardStatus();
                break;

            case 'bot_currency':
                $this->botCurrency();
                break;
            case "bot_language":
                $this->botLanguage();
                break;
            case "bot_supported_currencies":
                $this->botSupportedCurrencies();
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

    /**
     * @return void
     * @throws TelegramSDKException
     */
    private function botStatus(): void
    {
        wHook()->bot()->settings->bot_status = $this->params[1];
        wHook()->bot()->settings->save();
        BotSettingsFeature::menu()
            ->answer(__('tbe::bot_settings.main.answers.botStatusUpdated', [
                'newStatus' => $this->params[1] ? __('tbe::general.status.enabled') : __('tbe::general.status.disabled')
            ]))
            ->update();
    }

    /**
     * @return void
     * @throws TelegramSDKException
     */
    private function payWithCardStatus(): void
    {
        wHook()->bot()->settings->pay_with_card = $this->params[1];
        wHook()->bot()->settings->save();
        BotSettingsFeature::menu()
            ->answer(__('tbe::bot_settings.main.answers.payWithCardStatusUpdated', [
                'newStatus' => $this->params[1] ? __('tbe::general.status.enabled') : __('tbe::general.status.disabled')
            ]))
            ->update();
    }

    /**
     * @return void
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    private function changePaymentCardNumber(): void
    {
        $text = __('tbe::bot_settings.main.text.changePaymentCardNumber');

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
        $this->answer(__('tbe::bot_settings.main.answers.paymentCardNumber'));
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function changePaymentCardName(): void
    {
        $text = __('tbe::bot_settings.main.text.changePaymentCardName');

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
        $this->answer(__('tbe::bot_settings.main.answers.paymentCardName'));
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function changeTransactionsChatId(): void
    {
        $text = __('tbe::bot_settings.main.text.transactionsChatId');

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
        $this->answer(__('tbe::bot_settings.main.answers.transactionsChatId'));
    }

    /**
     * @throws TelegramSDKException
     */
    private function botLanguage(): void
    {
        $newLanguage = wHook()->bot()->settings->language == 'en' ? 'fa' : 'en';
        wHook()->bot()->settings->language = $newLanguage;
        wHook()->bot()->settings->save();
        App::setLocale(wHook()->bot()->settings->language);
        BotSettingsFeature::menu()
            ->answer(__('tbe::bot_settings.main.answers.botLanguage', [
                'language' => $newLanguage
            ]))
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function botSupportedCurrencies()
    {
        BotSettingsFeature::supportedCurrencies()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function start(): void
    {
        BotSettingsFeature::menu()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function currencyStatus(): void
    {
        $currency = $this->params[1];
        $status = $this->params[2];
        $status ? wHook()->bot()->settings->currencies()->create(['name' => $currency]) : wHook()->bot()->settings->currencies()->where('name', $currency)->delete();
        BotSettingsFeature::supportedCurrencies()
            ->answer(__('tbe::bot_settings.main.answers.botCurrency', [
                'currency' => $currency,
                'status' => $status ? __('tbe::general.status.enabled') : __('tbe::general.status.disabled')
            ]))
            ->update();
    }
}
