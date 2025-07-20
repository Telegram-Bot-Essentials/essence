<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
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

            case "bot_language":
                $this->botLanguage();
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

            case "gateways":
                $this->gateways();
                break;

            case "to_card":
                $this->toCard();
                break;

            case "zibal":
                $this->zibal();
                break;
            case "switch_zibal_status":
                $this->switchZibalStatus();
                break;
            case "change_zibal_merchant":
                $this->changeZibalMerchant();
                break;

            case "zarinpal":
                $this->zarinpal();
                break;
            case "zarinpal_status":
                $this->switchZarinpalStatus();
                break;
            case "change_zarinpal_token":
                $this->changeZarinpalMerchant();
                break;

            case "idpay":
                $this->idpay();
                break;

            case "nextpay":
                $this->nextpay();
                break;

            case "nowpayments":
                $this->nowpayments();
                break;

            case "zirgozar":
                $this->zirgozar();
                break;
            case "zirgozar_status":
                $this->switchZirgozarStatus();
                break;
            case "change_zirgozar_token":
                $this->changeZirgozarMerchant();
                break;
        }
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
        BotSettingsFeature::toCard()
            ->answer(__('tbe::bot_settings.main.answers.payWithCardStatusUpdated', [
                'newStatus' => $this->params[1] ? __('tbe::general.status.enabled') : __('tbe::general.status.disabled')
            ]))
            ->update();
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
     * @throws TelegramSDKException
     */
    private function gateways()
    {
        BotSettingsFeature::gateways()
            ->update();
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

    private function toCard(): void
    {
        BotSettingsFeature::toCard()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function zibal(): void
    {
        BotSettingsFeature::zibal()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function switchZibalStatus(): void
    {
        wHook()->bot()->settings->zibal = $this->params[1];
        wHook()->bot()->settings->save();
        BotSettingsFeature::zibal()->answer(__('tbe::general.answers.resourceFieldUpdatedSuccessfully', [
            'resource' => __('tbe::bot_settings.zibal.name')
        ]))->update();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function changeZibalMerchant(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction();

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_zibal_merchant", [
            'message_meta_id' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::bot_settings.zibal.text.setMerchant'),
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        $this->answer(__('tbe::bot_settings.zibal.answers.updatingMerchant'));
    }

    /**
     * @throws TelegramSDKException
     */
    private function zarinpal(): void
    {
        BotSettingsFeature::zarinpal()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function switchZarinpalStatus(): void
    {
        wHook()->bot()->settings->zarinpal = $this->params[1];
        wHook()->bot()->settings->save();
        BotSettingsFeature::zarinpal()->answer(__('tbe::general.answers.resourceFieldUpdatedSuccessfully', [
            'resource' => __('tbe::bot_settings.zarinpal.name')
        ]))->update();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function changeZarinpalMerchant(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction();

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_zarinpal_merchant", [
            'message_meta_id' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::bot_settings.zarinpal.text.setMerchant'),
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        $this->answer(__('tbe::bot_settings.zarinpal.answers.updatingToken'));
    }

    private function idpay(): void
    {
        $this->answer('Currently not supported');
    }

    private function nextpay(): void
    {
        $this->answer('Currently not supported');
    }

    private function nowpayments(): void
    {
        $this->answer('Currently not supported');
    }

    /**
     * @throws TelegramSDKException
     */
    private function zirgozar(): void
    {
        BotSettingsFeature::zirgozar()
            ->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function switchZirgozarStatus(): void
    {
        wHook()->bot()->settings->zirgozar = $this->params[1];
        wHook()->bot()->settings->save();
        BotSettingsFeature::zirgozar()->answer(__('tbe::general.answers.resourceFieldUpdatedSuccessfully', [
            'resource' => __('tbe::bot_settings.zirgozar.name')
        ]))->update();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function changeZirgozarMerchant(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction();

        wHook()->user()->changeState(encodeAnswerState($this->type, "change_zibal_merchant", [
            'message_meta_id' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::bot_settings.zirgozar.text.setToken'),
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        $this->answer(__('tbe::bot_settings.zirgozar.answers.updatingToken'));
    }
}
