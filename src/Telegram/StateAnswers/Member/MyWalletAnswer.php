<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Member;

use App\Models\Order;
use App\Telegram\Features\Member\BuyServiceFeature;
use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\CreditOrder;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\Feature\InvoiceFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Exceptions\TelegramSDKException;

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
    public function handle(string $method, array $params): void
    {
        $this->params = $params;
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
        $amount = floatval($amount);

        $creditOrder = CreditOrder::create([
            'bot_user_id' => wHook()->user()->id,
            'amount' => $amount
        ]);

        $invoice = $creditOrder->invoice()->create([
            'bot_user_id' => wHook()->user()->id,
            'price' => $creditOrder->amount
        ]);

        InvoiceFeature::invoice($invoice)->send();
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        // TODO: Implement cancel() method.
        // Logic to revert the process if user cancels action

        // example:
        $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        if ($messageMeta) {
            $messageMeta->continueAction();
        }
    }
}
