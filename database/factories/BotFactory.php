<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
        // Real Telegram-shaped, always-truthy tokens. randomNumber() could
        // return 0, and a falsy bot_token makes the SDK fall back to the
        // (unset) TELEGRAM_BOT_TOKEN env var and throw.
        return [
            'bot_token' => fake()->unique()->numberBetween(100_000_000, 999_999_999).':'.Str::random(35),
            'unique_id' => Uuid::uuid4()->toString(),
            'secret_token' => Str::random(40),
            'bot_owner_peer_id' => TelegramUser::factory()->create()->peer_id,
        ];
    }
}
