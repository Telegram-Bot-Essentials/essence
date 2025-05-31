<?php

namespace Elyar\TelegramBotEssentials\Jobs;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class InvoiceFailedHookJob implements ShouldQueue
{
    use Queueable;

    private Invoice $invoice;
    private Api $api;
    private Update $update;
    private Bot $bot;
    private BotUser $botUser;

    /**
     * Create a new job instance.
     */
    public function __construct(Api $api, Update $update, Bot $bot, BotUser $botUser, Invoice $invoice)
    {
        $this->api = $api;
        $this->update = $update;
        $this->bot = $bot;
        $this->botUser = $botUser;
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        wHook()->setApi($this->api);
        wHook()->setUpdate($this->update);
        wHook()->setBot($this->bot);
        wHook()->setUser($this->botUser);

        $payable = $this->invoice->payable ?? null;

        if ($payable && method_exists($payable, 'invoiceFailedHook')) {
            $payable->invoiceFailedHook();
        }
    }
}
