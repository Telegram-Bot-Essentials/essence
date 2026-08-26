<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * @extends Factory<Bot>
 */
class BotFactory extends Factory
{
    protected $model = Bot::class;

    public function definition(): array
    {
        return [
            'bot_token' => fake()->unique()->randomNumber(),
            'unique_id' => Uuid::uuid4()->toString(),
            'secret_token' => fake()->unique()->randomNumber(),
            'bot_owner_peer_id' => TelegramUser::factory()->create()->peer_id,
        ];
    }
}
