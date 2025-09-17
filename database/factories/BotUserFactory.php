<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
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
            'suspend' => fake()->boolean(),
        ];
    }
}
