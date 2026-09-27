<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

uses(RefreshDatabase::class);

function sentAlertRecipients(): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains((string) $pair[0]->url(), '/sendMessage'))
        ->map(fn ($pair) => (int) $pair[0]['chat_id'])
        ->sort()
        ->values()
        ->all();
}

it('alerts the owner and every reachable, unsuspended admin', function () {
    $bot = $this->makeBot();
    $this->makeBotUser($bot, 111, ['power' => Roles::ADMIN->value]);
    $this->makeBotUser($bot, 222, ['power' => Roles::ADMIN->value, 'suspend' => true]);
    $this->makeBotUser($bot, 333, ['power' => Roles::ADMIN->value, 'status' => BotUser::STATUS_BLOCKED]);
    $this->makeBotUser($bot, 444, ['power' => Roles::MODERATOR->value]);

    expect(adminAlert('test.alert', 'Channel lock is broken', $bot))->toBeTrue()
        ->and(sentAlertRecipients())->toBe(collect([111, (int) $bot->bot_owner_peer_id])->sort()->values()->all());

    $this->assertTelegramSent(fn ($request) => $request['text'] === '⚠️ Channel lock is broken');
});

it('does not alert the owner twice when they are also an admin', function () {
    $bot = $this->makeBot();
    BotUser::factory()->create([
        'bot_id' => $bot->id,
        'telegram_user_peer_id' => $bot->bot_owner_peer_id,
        'power' => Roles::ADMIN->value,
    ]);

    adminAlert('test.alert', 'x', $bot);

    expect(sentAlertRecipients())->toBe([(int) $bot->bot_owner_peer_id]);
});

it('throttles the same key per bot', function () {
    $bot = $this->makeBot();
    $other = $this->makeBot();

    expect(adminAlert('test.alert', 'first', $bot))->toBeTrue()
        ->and(adminAlert('test.alert', 'again', $bot))->toBeFalse()
        ->and(adminAlert('test.other', 'another key', $bot))->toBeTrue()
        ->and(adminAlert('test.alert', 'another bot', $other))->toBeTrue();
});

it('renders a closure in the bot locale and restores the app locale', function () {
    $bot = $this->makeBot();
    app()->instance(ResolvesBotLocale::class, new class implements ResolvesBotLocale
    {
        public function resolve(Bot $bot): string
        {
            return 'de';
        }
    });
    app()->setLocale('fa');

    adminAlert('test.alert', fn () => 'locale='.app()->getLocale(), $bot);

    $this->assertTelegramSent(fn ($request) => $request['text'] === '⚠️ locale=de');
    expect(app()->getLocale())->toBe('fa');
});

it('drops the alert when there is no bot to alert', function () {
    expect(adminAlert('test.alert', 'x'))->toBeFalse();

    Http::assertNothingSent();
});

it('reads a throttle that came from the environment as a string', function () {
    config()->set('tbe-essence.admin_alerts.throttle', '60');
    $bot = $this->makeBot();

    expect(adminAlert('test.alert', 'x', $bot))->toBeTrue()
        ->and(adminAlert('test.alert', 'x', $bot))->toBeFalse();
});
