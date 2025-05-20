<?php

namespace Elyar\TelegramBotEssentials\Database\factories;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotUserFactory extends Factory
{
    protected $model = BotUser::class;
    public function definition(): array
    {
        $role = Roles::cases()[rand(0, count(Roles::cases()) - 1)];
        $telegramUser = TelegramUser::factory()->create();
        return [
            'bot_id' => Bot::factory()->create([
                'bot_owner_peer_id' => $telegramUser->peer_id,
            ])->id,
            'telegram_user_peer_id' => $telegramUser->peer_id,
            'power' => $role,
            'balance' => rand(0, 10) * 25000,
            'state' => ['test', null][rand(0, 1)],
            'menu' => $role == Roles::ADMIN->value ? 'admin' : 'main',
            'suspend' => fake()->unique()->boolean(),
        ];
    }
}
