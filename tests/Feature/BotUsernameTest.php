<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Models\Bot;

uses(RefreshDatabase::class);

function getMeAnswers(array $body, int $status = 200): void
{
    $factory = new Factory;
    $factory->fake(['*' => Http::response($body, $status)]);
    Http::swap($factory);
}

it('asks telegram again only a while after a failed username lookup', function () {
    $bot = Bot::factory()->create(['bot_token' => '1:dead']);
    getMeAnswers(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401);

    expect($bot->username())->toBeNull()
        ->and($bot->username())->toBeNull();
    Http::assertSentCount(1);

    $this->travel(6)->minutes();
    getMeAnswers(['ok' => true, 'result' => ['id' => 1, 'is_bot' => true, 'first_name' => 'Shop', 'username' => 'shop_bot']]);

    expect($bot->username())->toBe('shop_bot');
});
