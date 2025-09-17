<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Attempts\ToZirgozarAttempt;
use TelegramBotEssentials\Essence\Models\Billing\Invoice;
use TelegramBotEssentials\Essence\Services\CurrencyFather;
use Exception;
use Http;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;

class GatewayZirgozarController extends Controller
{
    /**
     * @param string $token
     * @param Request $request
     * @return ResponseFactory|Application|RedirectResponse|Response|Redirector|object
     */
    function pay(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        if ($invoice->status == 'paid') {
            return response('invoice is already paid', 400);
        }

        $response = $this->initializeWebPay($invoice, $token);

        $toZirgozarAttempt = ToZirgozarAttempt::create([
            'payment_code' => $response['code'],
            'payment_token' => $response['token'],
            'amount' => $invoice->price
        ]);

        billing()->attemptPayment($invoice, $toZirgozarAttempt);

        return redirect($response['link']);
    }

    private function initializeWebPay(Invoice $invoice, string $token): array
    {
        try{
            $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
            $data = [
                'key' => $invoice->bot->settings->zirgozar_token,
                'action' => 'web_pay',
                'mobile' => $invoice->botUser->telegramUser->tel,
                'amount' => CurrencyFather::from($invoice->bot->currency)->amount($invoice->price)->toIRT(),
                'callback_url' => route('invoice.zirgozar.callback', ['token' => $token]),
            ];

            $data = array_filter($data);
            $response = Http::post($url, $data);
            $response = $response->json();
            if(!$response || !$response['result']) {
                \Log::error($response['error_desc'] ?? 'error is not provided');
                throw new HttpResponseException(apiResponse()->error('Failed to initialize web payment', 503));
            }
            return $response;
        } catch (Exception $e){
            exceptionReport($e);
            return [];
        }
    }

    /**
     * @param string $token
     * @param Request $request
     * @return ResponseFactory|Factory|View|Application|Response|\Illuminate\View\View|object
     * @throws TelegramSDKException
     * @throws TenantCouldNotBeIdentifiedById
     */
    function callback(string $token, Request $request)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        if ($invoice->status == 'paid') {
            try {
                $api = new Api($invoice->bot->bot_token);
                $me = $api->getMe();
                $username = $me->username;
            } catch (TelegramSDKException $e) {
                Log::error($e->getMessage());
                return response('Failed to redirect', 503);
            }
            $botLink = 'https://t.me/' . $username . '?start=invoice_' . $invoice->id;
            return response('invoice is already paid, go to ' . "<a href=\"{$botLink}\">Telegram</a>", 200);
        }

        try {
            $paymentToken = $request->input('token') ?? throw new \LogicException('Payment token is not provided');
            $result = $request->input('result') ?? throw new \LogicException('Result is not provided');
            $orderId = $request->input('order_id') ?? null;
        } catch (\LogicException $e) {
            return response($e->getMessage(), 400);
        }

        $this->initializeWHook($invoice);
        $response = $this->getWebPayResult($paymentToken);

        if(!($invoice->paymentAttempt instanceof ToZirgozarAttempt) || !($invoice->paymentAttempt instanceof PaymentAttempt)) return apiResponse()->error('Failed to handle payment', 503);

        $zirGozarAttempt = $invoice->paymentAttempt;
        $zirGozarAttempt->update([
            'payer_mobile' => $response['payer_mobile'] ?? 'N/A',
            'payer_card' => $response['payer_card'] ?? 'N/A',
            'received_amount' => $response['amount'],
        ]);

        if ($response['status'] == 'paid') {
            $zirGozarAttempt->attemptSucceed();
        } elseif ($response['status'] == 'unpaid') {
            $zirGozarAttempt->attemptFailed();
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
            'gateway' => 'zirgozar',
            'invoice' => $invoice,
            'botLink' => $botLink,
        ]);
    }

    /**
     * @throws TelegramSDKException
     * @throws TenantCouldNotBeIdentifiedById
     */
    function initializeWHook(Invoice $invoice): void
    {
        tenancy()->initialize($invoice->bot);
        wHook()::setBot($invoice->bot);
        wHook()::setApi(new Api($invoice->bot->bot_token));
        wHook()::setUser($invoice->botUser);
        wHook()::setUpdate(Update::make(request()->all()));
    }

    private function getWebPayResult(string $paymentToken): array
    {
        try{
            $url = config('telegram-bot-essentials.gateways.zirgozar.url') . '/api/index.php';
            $data = [
                'key' => wHook()->bot()->settings->zirgozar_token,
                'action' => 'web_pay_status',
                'token' => $paymentToken,
            ];

            $response = Http::post($url, $data);
            $response = $response->json();
            if(!$response || !$response['result']) {
                \Log::error($response['error_desc'] ?? 'error is not provided');
                throw new HttpResponseException(apiResponse()->error('Failed to handle payment', 503));
            }
            return $response;
        } catch (Exception $e){
            exceptionReport($e);
            return [];
        }
    }
}
