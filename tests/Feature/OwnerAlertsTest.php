<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Services\OwnerAlerts;

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

it('alerts the bot owner', function () {
    $bot = $this->makeBot();

    expect(ownerAlert('test.alert', 'Channel lock is broken', $bot))->toBeTrue()
        ->and(sentAlertRecipients())->toBe([(int) $bot->bot_owner_peer_id]);

    $this->assertTelegramSent(fn ($request) => $request['text'] === '⚠️ Channel lock is broken');
});

it('alerts whoever an app that extends OwnerAlerts names', function () {
    $bot = $this->makeBot();
    app()->singleton(OwnerAlerts::class, fn () => new class extends OwnerAlerts
    {
        protected function recipients(Bot $bot): array
        {
            return [...parent::recipients($bot), 111];
        }
    });

    ownerAlert('test.alert', 'x', $bot);

    expect(sentAlertRecipients())->toBe(collect([111, (int) $bot->bot_owner_peer_id])->sort()->values()->all());
});

it('throttles the same key per bot', function () {
    $bot = $this->makeBot();
    $other = $this->makeBot();

    expect(ownerAlert('test.alert', 'first', $bot))->toBeTrue()
        ->and(ownerAlert('test.alert', 'again', $bot))->toBeFalse()
        ->and(ownerAlert('test.other', 'another key', $bot))->toBeTrue()
        ->and(ownerAlert('test.alert', 'another bot', $other))->toBeTrue();
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

    ownerAlert('test.alert', fn () => 'locale='.app()->getLocale(), $bot);

    $this->assertTelegramSent(fn ($request) => $request['text'] === '⚠️ locale=de');
    expect(app()->getLocale())->toBe('fa');
});

it('drops the alert when there is no bot to alert', function () {
    expect(ownerAlert('test.alert', 'x'))->toBeFalse();

    Http::assertNothingSent();
});

it('reads a throttle that came from the environment as a string', function () {
    config()->set('tbe-essence.owner_alerts.throttle', '60');
    $bot = $this->makeBot();

    expect(ownerAlert('test.alert', 'x', $bot))->toBeTrue()
        ->and(ownerAlert('test.alert', 'x', $bot))->toBeFalse();
});
