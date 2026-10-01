<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Models\MessageMeta;

uses(RefreshDatabase::class);

/** The inline keyboard the lock put on the message, as text. */
function lockKeyboard(): string
{
    return Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/editMessageReplyMarkup'))
        ->map(fn ($pair) => json_encode(json_decode((string) $pair[0]['reply_markup'], true), JSON_UNESCAPED_UNICODE))
        ->last();
}

beforeEach(function () {
    $bot = $this->makeBot();
    $this->makeBotUser($bot, 555);

    // A real inbound request populates wHook() for the rest of the test.
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('hi', peerId: 555))->assertOk();

    $factory = new Factory;
    $factory->fake(['*' => Http::response(['ok' => true, 'result' => true])]);
    Http::swap($factory);

    $this->meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Menu']);
});

it('labels the cancelable lock in english', function () {
    App::setLocale('en');

    $this->meta->cancelableLockAction();

    expect(lockKeyboard())->toContain('🔒 Locked For Action')->toContain('🗑️ Cancel');
});

it('labels the cancelable lock in persian', function () {
    App::setLocale('fa');

    $this->meta->cancelableLockAction();

    expect(lockKeyboard())->toContain('🔒 قفل برای انجام عملیات')->toContain('🗑️ لغو')->not->toContain('Cancel');
});

it('labels the plain lock in the active locale', function () {
    App::setLocale('fa');

    $this->meta->lockAction();

    expect(lockKeyboard())->toContain('🔒 قفل برای انجام عملیات');
});

it('uses a label passed in as given', function () {
    App::setLocale('fa');

    $this->meta->cancelableLockAction('Playing');

    expect(lockKeyboard())->toContain('🔒 Playing')->toContain('🗑️ لغو');
});
