<?php

namespace Elyar\TelegramBotEssentials\Database\factories;

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotFactory extends Factory
{
    protected $model = Bot::class;
    public function definition(): array
    {
        return [
            'bot_token' => fake()->unique()->randomNumber(),
            'unique_id' => fake()->unique()->randomNumber(),
            'secret_token' => fake()->unique()->randomNumber(),
            'bot_owner_peer_id' => TelegramUser::factory()->create()->peer_id,
        ];
    }
}
