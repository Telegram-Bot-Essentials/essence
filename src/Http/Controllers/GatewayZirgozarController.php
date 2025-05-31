<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Elyar\TelegramBotEssentials\Models\ToZirgozarAttempt;
use Elyar\TelegramBotEssentials\Services\CurrencyFather;
use Exception;
use Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Telegram\Bot\Api;

class GatewayZirgozarController extends Controller
{
    /**
     * @throws ConnectionException
     */
    function pay(string $invoiceId, Request $request)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
        $data = [
            'key' => config('telegram-bot-essentials.gateways.zirgozar.token'),
            'action' => 'web_pay',
            'mobile' => $invoice->botUser->telegramUser->tel,
            'amount' => CurrencyFather::from($invoice->bot->settings->default_currency)->amount($invoice->price)->toIRT(),
            'callback_url' => route('invoice.zirgozar.callback', ['invoiceId' => $invoiceId]),
        ];

        $data = array_filter($data);
        $response = Http::post($url, $data);
        $response = $response->json();
        if(!$response['result']) {
            \Log::error($response['error_desc']);
            return response('Failed to pay', 503);
        }

        $zirgozarAttempt = ToZirgozarAttempt::create([
            'payment_code' => $response['code'],
            'payment_token' => $response['token'],
        ]);

        $invoice->paymentAttempt()->associate($zirgozarAttempt);
        $invoice->save();

        $link = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/portal/?token=' . $response['token'];
        return redirect($link);
    }

    /**
     * @throws ConnectionException
     */
    function callback(string $invoiceId, Request $request)
    {
        try {
            $paymentToken = $request->input('token') ?? throw new \LogicException('Payment token is not provided');
            $result = $request->input('result') ?? throw new \LogicException('Result is not provided');
            $orderId = $request->input('order_id') ?? null;
        }catch (\LogicException $e){
            return response($e->getMessage(), 400);
        }

        $invoice = Invoice::findOrFail($invoiceId);
        $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
        $data = [
            'key' => config('telegram-bot-essentials.gateways.zirgozar.token'),
            'action' => 'web_pay_status',
            'token' => $paymentToken,
        ];

        $response = Http::post($url, $data);

        if(!$response->json()['result']) {
            \Log::error($response->json()['error_desc']);
            return response('Failed to handle payment', 503);
        }
        if(!($invoice->paymentAttempt instanceof ToZirgozarAttempt)) return response('Failed to handle payment', 503);

        if($response['status'] == 'paid'){
            $invoice->paymentAttempt->attemptSucceed();
        }elseif ($response['status'] == 'unpaid'){
            $invoice->paymentAttempt->attemptFailed();
        }

        try {
            $api = new Api($invoice->bot->bot_token);
            $me = $api->getMe();
            $username = $me->username;
        }catch (Exception $e){
            \Log::error($e->getMessage());
            return response('Failed to redirect', 503);
        }

        return redirect('https://t.me/' . $username . '?start=invoice_' . $invoice->id);
    }
}
