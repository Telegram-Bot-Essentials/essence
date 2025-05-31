<?php

namespace Elyar\TelegramBotEssentials\Database\factories;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\CreditOrder;
use Elyar\TelegramBotEssentials\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;
    public function definition(): array
    {
        return [
            'bot_id' => Bot::first()->id,
            'bot_user_id' => BotUser::first()->id,
            'payable_id' => CreditOrder::first()->id,
            'payable_type' => CreditOrder::class,
            'price' => 5000,
        ];
    }
}
