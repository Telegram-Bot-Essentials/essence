<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;

/*
 * ReplyKey labels are locale-lazy.
 *
 * A ReplyKey holds a translation key and resolves it inside getText(), so
 * a single registered instance serves every locale and every bot. The bus
 * keys on the class name, which does not move when the locale does.
 *
 * Before this, handlers resolved __() in their constructors and the bus
 * keyed on the resulting string, so registering once per worker would have
 * pinned every bot to whichever locale was active at boot.
 */

const EN_CANCEL = 'Cancel Process ❌';
const FA_CANCEL = 'لغو فرآیند ❌';

it('keys the bus by class name, not by label', function () {
    App::setLocale('en');
    $bus = new ReplyKeyBus;
    $bus->addReplyKey(CancelProcessKey::class);

    expect(array_keys($bus->getReplyKeys()))->toBe([CancelProcessKey::class]);
});

it('resolves the label in the locale active when it is read', function () {
    $bus = new ReplyKeyBus;
    App::setLocale('en');
    $bus->addReplyKey(CancelProcessKey::class);

    $key = $bus->getReplyKeys()[CancelProcessKey::class];
    expect($key->getText())->toBe(EN_CANCEL);

    App::setLocale('fa');
    expect($key->getText())->toBe(FA_CANCEL);
});

it('resolves the response in the locale active when it is read', function () {
    $key = new CancelProcessKey;

    App::setLocale('en');
    $en = $key->getResponse();

    App::setLocale('fa');
    expect($key->getResponse())->not->toBe($en)->and($key->getResponse())->not->toBeEmpty();
});

it('registers one entry however many locales it is registered under', function () {
    $bus = new ReplyKeyBus;

    App::setLocale('en');
    $bus->addReplyKey(CancelProcessKey::class);

    App::setLocale('fa');
    $bus->addReplyKey(CancelProcessKey::class);

    expect($bus->getReplyKeys())->toHaveCount(1);
});

it('registers the built-in cancel key on the container bus at boot', function () {
    expect(array_keys(app(ReplyKeyBus::class)->getReplyKeys()))
        ->toContain(CancelProcessKey::class);
});

it('requires every key to declare a label, enforced by PHP rather than at runtime', function () {
    // text() is abstract: a class that skips it now fails to compile at
    // all, instead of the runtime LogicException getText() used to throw
    // on an unset $textKey.
    expect((new ReflectionMethod(ReplyKey::class, 'text'))->isAbstract())->toBeTrue();
});
