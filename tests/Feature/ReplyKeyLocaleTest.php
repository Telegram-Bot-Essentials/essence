<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;

/*
 * Characterisation tests for how ReplyKeyBus and locale interact.
 *
 * ReplyKey subclasses resolve __() inside their constructors, and the bus
 * keys its map by the resulting translated string. That coupling is why
 * TelegramWebhookController re-scans and re-registers every handler on
 * every request: it is what makes per-bot language work.
 *
 * These tests pin that today, so the locale-lazy refactor has to state
 * explicitly which of them it changes.
 */

const EN_CANCEL = 'Cancel Process ❌';
const FA_CANCEL = 'لغو فرآیند ❌';

it('keys the bus by the translated text of the active locale', function () {
    App::setLocale('en');
    $bus = new ReplyKeyBus;
    $bus->addReplyKey(CancelProcessKey::class);

    expect(array_keys($bus->getReplyKeys()))->toBe([EN_CANCEL]);
});

it('keys the bus differently under a different locale', function () {
    App::setLocale('fa');
    $bus = new ReplyKeyBus;
    $bus->addReplyKey(CancelProcessKey::class);

    expect(array_keys($bus->getReplyKeys()))->toBe([FA_CANCEL]);
});

it('freezes a handler text at construction time', function () {
    // The load-bearing behaviour. A handler instantiated under one locale
    // keeps that locale's text forever, so registering once per worker
    // boot instead of once per request would pin every bot to whichever
    // locale happened to be active at boot.
    App::setLocale('en');
    $bus = new ReplyKeyBus;
    $bus->addReplyKey(CancelProcessKey::class);

    App::setLocale('fa');

    $key = $bus->getReplyKeys()[EN_CANCEL];

    expect($key->getText())->toBe(EN_CANCEL)
        ->and(array_keys($bus->getReplyKeys()))->not->toContain(FA_CANCEL);
});

it('re-registering under a new locale adds a key instead of replacing it', function () {
    // A singleton bus that survives across requests accumulates one entry
    // per locale it has ever seen, so a bot in one language can match a
    // button labelled in another.
    App::setLocale('en');
    $bus = new ReplyKeyBus;
    $bus->addReplyKey(CancelProcessKey::class);

    App::setLocale('fa');
    $bus->addReplyKey(CancelProcessKey::class);

    expect(array_keys($bus->getReplyKeys()))->toBe([EN_CANCEL, FA_CANCEL]);
});

it('registers the built-in cancel key on the container bus at boot', function () {
    expect(array_keys(app(ReplyKeyBus::class)->getReplyKeys()))->toContain(EN_CANCEL);
});
