<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;

class GatewayZibalController extends Controller
{
    /**
     * @throws TelegramSDKException
     * @throws TenantCouldNotBeIdentifiedById
     */
    function pay(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        $this->initializeWHookByInvoice($invoice);

        $result = gateways()->zibal()->paymentRequest(priceIn($invoice->price)->toIRR(), route('invoice.zibal.callback', ['token' => $token]))->execute();

        if($result['result'] != 100) {
            Log::error($result['message'] ?? 'error message is not provided');
        }

        return apiResponse()->success($result);
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
        wHook()::setBot($invoice->bot);
        wHook()::setApi(new Api($invoice->bot->bot_token));
        wHook()::setUser($invoice->botUser);
        wHook()::setUpdate(Update::make(request()->all()));
    }
}
