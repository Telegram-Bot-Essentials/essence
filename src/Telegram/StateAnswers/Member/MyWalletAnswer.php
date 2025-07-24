<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Member;

use App\Models\Order;
use App\Telegram\Features\Member\BuyServiceFeature;
use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\CreditOrder;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\Feature\InvoiceFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class MyWalletAnswer extends StateAnswer
{
    protected string $type = 'MYWALLET';
    protected int $perm = Roles::MEMBER->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

    /**
     * @throws TelegramSDKException
     */
    public function handle(string $method): void
    {
        switch (strtolower($method)) {
            case "add_credit":
                $this->addCredit();
                break;
            case "cancel":
                $this->cancel();
                break;
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function addCredit(): void
    {
        $amount = wHook()->update()->message->text;
        Validator::validate(
            ['amount' => $amount],
            ['amount' => "required|numeric|min:0.01|max:100000000"]
        );

        $creditOrder = CreditOrder::create([
            'bot_user_id' => wHook()->user()->id,
            'amount' => $amount
        ]);

        $invoice = billing()->createInvoice($creditOrder);

        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Creating invoice for amount of " . currency()->priceFormat($amount) . " 💸", // TODO: Localize this message
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $replyMarkup = Keyboard::make()->inline();
        \Log::error(json_encode($replyMarkup->all()));
        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "test",
                'callback_data' => encodeCallback('test', ['test', $invoice->id])
            ])
        ]);
        \Log::error(json_encode($replyMarkup->all()));

        InvoiceFeature::invoice($invoice)->send();
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        if ($messageMeta) {
            $messageMeta->continueAction();
        }
    }
}
