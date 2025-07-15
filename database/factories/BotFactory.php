<?php

namespace Elyar\TelegramBotEssentials\Database\factories;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class BotFactory extends Factory
{
    protected $model = Bot::class;
    public function definition(): array
    {
        return [
            'bot_token' => fake()->unique()->randomNumber(),
            'unique_id' => Uuid::uuid4()->toString(),
            'secret_token' => fake()->unique()->randomNumber(),
            'currency' => collect(config('telegram-bot-essentials.supported_currencies'))->random()['name'] ?? 'USD',
            'bot_owner_peer_id' => TelegramUser::factory()->create()->peer_id,
        ];
    }
}
