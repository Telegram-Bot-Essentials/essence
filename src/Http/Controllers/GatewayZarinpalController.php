<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Services\CurrencyFather;
use TelegramBotEssentials\Essence\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Attempts\ToZirgozarAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Invoice;

class GatewayZarinpalController extends Controller
{
    /**
     * @throws TelegramSDKException
     * @throws TenantCouldNotBeIdentifiedById
     */
    function pay(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        $this->initializeWHookByInvoice($invoice);

        $result = gateways()->zarinpal()->PaymentRequest(priceIn($invoice->price)->toIRT(), 'test', route('invoice.zarinpal.callback', ['token' => $token]))->execute();

//        return apiResponse()->success($result);
    }

    function callback(string $token, Request $request)
    {

    }

    /**
     * @throws TelegramSDKException
     * @throws TenantCouldNotBeIdentifiedById
     */
    private function initializeWHookByInvoice(Invoice $invoice): void
    {
        tenancy()->initialize($invoice->bot);
        wHook()->setBot($invoice->bot);
        wHook()->setApi(telegramApi($invoice->bot->bot_token));
        wHook()->setUser($invoice->botUser);
        wHook()->setUpdate(Update::make(request()->all()));
    }
}
