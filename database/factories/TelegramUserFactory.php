<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TelegramUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'peer_id' => fake()->unique()->randomNumber(),
            'first_name' => fake()->unique()->name(),
            'last_name' => fake()->unique()->name(),
            'username' => fake()->unique()->name(),
            'tel' => fake()->unique()->phoneNumber(),
        ];
    }
}
