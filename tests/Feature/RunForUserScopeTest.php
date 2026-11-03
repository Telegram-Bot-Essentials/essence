<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;

uses(RefreshDatabase::class);

/*
 * runForUser() switches who the webhook is acting for and nothing else. What
 * to do about a failure belongs to whoever called it.
 */

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->caller = $this->makeBotUser($this->bot, 9001);
    $this->other = $this->makeBotUser($this->bot, 9002);

    wHook()->setBot($this->bot);
    wHook()->setUser($this->caller);
    wHook()->setApi(telegramApi((string) $this->bot->bot_token));
    wHook()->setUpdate(new Update(['update_id' => 1]));
});

it('hands a failure back to the caller and puts the user back', function () {
    $thrown = new RuntimeException('panel is down');

    expect(fn () => wHook()->runForUser($this->other, fn () => throw $thrown))
        ->toThrow($thrown);

    expect(wHook()->user()->getKey())->toBe($this->caller->getKey());
});

it('does not swallow an expected stop', function () {
    expect(fn () => wHook()->runForUser($this->other, fn () => dependsOn(false, 'nope')))
        ->toThrow(FeatureIsDisabled::class);

    expect(wHook()->user()->getKey())->toBe($this->caller->getKey());
});

it('says nothing to anyone about a failure', function () {
    rescue(fn () => wHook()->runForUser($this->other, fn () => throw new RuntimeException('boom')), report: false);

    $this->assertTelegramNotSent();
});

it('returns what the callback returns while acting for the other user', function () {
    $seen = wHook()->runForUser($this->other, fn () => wHook()->user()->getKey());

    expect($seen)->toBe($this->other->getKey());
});
