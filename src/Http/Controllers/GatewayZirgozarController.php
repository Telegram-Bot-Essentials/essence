<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Models\Invoice;
use Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
            'amount' => $invoice->price,
            'callback_url' => route('invoice.zirgozar.callback', ['invoiceId' => $invoiceId]),
        ];

        $data = array_filter($data);
        $response = Http::post($url, $data);
        if(!$response->json()['result']) {
            \Log::error($response->json()['error_desc']);
            return response('Failed to pay', 503);
        }



        $link = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/portal/?token=' . $response->json()['token'];
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

        if($response['status'] == 'paid'){
            $invoice->markAsPaid();
            return response('Payment accepted', 200);
        }elseif ($response['status'] == 'unpaid'){
            return response('Payment rejected', 400);
        }

        return response('Failed to handle payment', 503);
    }
}
