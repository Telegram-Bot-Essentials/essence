<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TelegramBotEssentials\Essence\Models\TelegramUser;

class TelegramUserFactory extends Factory
{
    protected $model = TelegramUser::class;

    public function definition(): array
    {
        return [
            'peer_id' => fake()->unique()->numberBetween(10000, 999999999),
            'first_name' => fake()->unique()->firstName(),
            'last_name' => fake()->unique()->lastName(),
            'username' => fake()->unique()->userName(),
            'tel' => fake()->unique()->phoneNumber(),
        ];
    }
}
