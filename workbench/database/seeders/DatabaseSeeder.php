<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = TelegramUser::query()->firstOrCreate(['peer_id' => 111], TelegramUser::factory()->raw(['peer_id' => 111, 'first_name' => 'Elyar']));
        $bot = Bot::factory()->create(['unique_id' => 'demo', 'bot_token' => '1:demo', 'bot_owner_peer_id' => $owner->peer_id, 'activated_until' => now()->addYear()]);
        BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => 111]);
        foreach ([222, 333, 444] as $peer) {
            TelegramUser::query()->firstOrCreate(['peer_id' => $peer], TelegramUser::factory()->raw(['peer_id' => $peer]));
            BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => $peer]);
        }

        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
