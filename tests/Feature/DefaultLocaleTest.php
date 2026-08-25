<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Support\DefaultBotLocaleResolver;
use TelegramBotEssentials\Essence\Support\WebhookContext;

uses(RefreshDatabase::class);

it('resolves the app locale by default, since essence has no locale data of its own', function () {
    config(['app.locale' => 'fa']);

    $bot = Bot::factory()->create();

    expect((new DefaultBotLocaleResolver)->resolve($bot))->toBe('fa');
});

it('sets the active locale from whatever is bound to ResolvesBotLocale when the webhook initializes', function () {
    $bot = Bot::factory()->create();

    config(['app.locale' => 'en']);
    app()->setLocale('en');

    botEventBus()->fire(new BotWebhookInitialized(new WebhookContext(
        botId: $bot->id,
        botUserId: null,
        bot: $bot,
    )));

    expect(app()->getLocale())->toBe('en');
});

it('lets a companion rebind ResolvesBotLocale without essence knowing about it', function () {
    $bot = Bot::factory()->create();

    app()->bind(ResolvesBotLocale::class, fn () => new class implements ResolvesBotLocale
    {
        public function resolve(Bot $bot): string
        {
            return 'fa';
        }
    });

    botEventBus()->fire(new BotWebhookInitialized(new WebhookContext(
        botId: $bot->id,
        botUserId: null,
        bot: $bot,
    )));

    expect(app()->getLocale())->toBe('fa');
});

it('does nothing when the webhook context has no bot to resolve a locale for', function () {
    app()->setLocale('fa');

    botEventBus()->fire(new BotWebhookInitialized(new WebhookContext(
        botId: null,
        botUserId: null,
    )));

    expect(app()->getLocale())->toBe('fa');
});
