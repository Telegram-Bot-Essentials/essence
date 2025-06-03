<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Models\ToZirgozarAttempt;
use Elyar\TelegramBotEssentials\Services\CurrencyFather;
use Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;

class GatewayZirgozarController extends Controller
{
    /**
     * @throws ConnectionException
     */
    function pay(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        if ($invoice->status == 'paid') {
            return response('invoice is already paid', 400);
        }

        $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
        $data = [
            'key' => config('telegram-bot-essentials.gateways.zirgozar.token'),
            'action' => 'web_pay',
            'mobile' => $invoice->botUser->telegramUser->tel,
            'amount' => CurrencyFather::from($invoice->currency)->amount($invoice->price)->toIRT(),
            'callback_url' => route('invoice.zirgozar.callback', ['token' => $token]),
        ];

        $data = array_filter($data);
        $response = Http::post($url, $data);
        $response = $response->json();
        if (!$response['result']) {
            \Log::error($response['error_desc']);
            return response('Failed to pay', 503);
        }

        $toZirgozarAttempt = ToZirgozarAttempt::create([
            'payment_code' => $response['code'],
            'payment_token' => $response['token'],
        ]);
        $invoice->paymentAttempt()->associate($toZirgozarAttempt);
        $invoice->save();

        $link = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/portal/?token=' . $response['token'];
        return redirect($link);
    }

    /**
     * @throws ConnectionException
     * @throws TenantCouldNotBeIdentifiedById
     * @throws TelegramSDKException
     */
    function callback(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

//        if ($invoice->status == 'paid') {
//            try {
//                $api = new Api($invoice->bot->bot_token);
//                $me = $api->getMe();
//                $username = $me->username;
//            } catch (TelegramSDKException $e) {
//                Log::error($e->getMessage());
//                return response('Failed to redirect', 503);
//            }
//            $botLink = 'https://t.me/' . $username . '?start=invoice_' . $invoice->id;
//            return response('invoice is already paid, go to ' . "<a href=\"{$botLink}\">Telegram</a>", 200);
//        }

        try {
            $paymentToken = $request->input('token') ?? throw new \LogicException('Payment token is not provided');
            $result = $request->input('result') ?? throw new \LogicException('Result is not provided');
            $orderId = $request->input('order_id') ?? null;
        } catch (\LogicException $e) {
            return response($e->getMessage(), 400);
        }

        tenancy()->initialize($invoice->bot);
        wHook()::setBot($invoice->bot);
        wHook()::setApi(new Api($invoice->bot->bot_token));
        wHook()::setUser($invoice->botUser);
        wHook()::setUpdate(Update::make($request->all()));

        $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
        $data = [
            'key' => config('telegram-bot-essentials.gateways.zirgozar.token'),
            'action' => 'web_pay_status',
            'token' => $paymentToken,
        ];

        $response = Http::post($url, $data);
        $response = $response->json();
        if (!$response['result']) {
            \Log::error($response['error_desc']);
            return response('Failed to handle payment', 503);
        }
        if(!($invoice->paymentAttempt instanceof ToZirgozarAttempt)) return response('Failed to handle payment', 503);

        $paymentAttempt = $invoice->paymentAttempt;
        $paymentAttempt->update([
            'payer_mobile' => $response['payer_mobile'],
            'payer_card' => $response['payer_card'],
            'amount' => $response['amount'],
        ]);

        if ($response['status'] == 'paid') {
            $paymentAttempt->attemptSucceed();
        } elseif ($response['status'] == 'unpaid') {
            $paymentAttempt->attemptFailed();
        }

        try {
            $api = new Api($invoice->bot->bot_token);
            $me = $api->getMe();
            $username = $me->username;
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage());
            return response('Failed to redirect', 503);
        }

        $botLink = 'https://t.me/' . $username . '?start=invoice_' . $invoice->id;
        return view('tbe::app', [
            'status' => $response['status'],
            'invoice' => $invoice,
            'botLink' => $botLink,
        ]);
    }
}
