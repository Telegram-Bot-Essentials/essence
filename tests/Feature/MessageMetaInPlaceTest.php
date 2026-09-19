<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

uses(RefreshDatabase::class);

function telegramCalled(string $method): bool
{
    return Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/'.$method))->isNotEmpty();
}

function fakeTelegram(array $failing = []): void
{
    $responses = [];

    foreach ($failing as $method => $description) {
        $responses['*/'.$method] = Http::response(['ok' => false, 'error_code' => 400, 'description' => 'Bad Request: '.$description], 400);
    }

    // A fresh factory: the base TestCase already registered a catch-all fake,
    // and stubs registered first win, so faking again would change nothing.
    $factory = new Factory;
    $factory->fake($responses + ['*' => Http::response(['ok' => true, 'result' => true])]);
    Http::swap($factory);
}

beforeEach(function () {
    $bot = $this->makeBot();
    $this->makeBotUser($bot, 555);

    // A real inbound request populates wHook() for the rest of the test.
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('hi', peerId: 555))->assertOk();
});

it('unlocks a message in place, restoring its keyboard without deleting or re-sending it', function () {
    $keyboard = Keyboard::make()->inline()->row([Keyboard::inlineButton(['text' => 'Open', 'callback_data' => 'X#open'])]);
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Menu', 'message_reply_markup' => $keyboard->toArray()]);

    fakeTelegram();
    $meta->continueAction();

    $this->assertTelegramSent(fn ($request) => str_ends_with((string) $request->url(), '/editMessageReplyMarkup')
        && $request['chat_id'] === 10
        && $request['message_id'] === 20
        && str_contains(json_encode($request['reply_markup']), 'X#open'));
    expect(telegramCalled('deleteMessage'))->toBeFalse()
        ->and(telegramCalled('sendMessage'))->toBeFalse();
});

it('unlocks a message that had no keyboard by clearing the lock buttons', function () {
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Menu']);

    fakeTelegram();
    $meta->continueAction();

    $this->assertTelegramSent(fn ($request) => str_ends_with((string) $request->url(), '/editMessageReplyMarkup')
        && ! array_key_exists('reply_markup', $request->data()));
});

it('updates a message in place and remembers the new content for a later revert', function () {
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Old']);
    $keyboard = Keyboard::make()->inline()->row([Keyboard::inlineButton(['text' => 'Back', 'callback_data' => 'X#back'])]);

    fakeTelegram();
    $meta->updateAndContinueAction(new TelegramResponse(text: 'New', replyMarkup: $keyboard));

    $this->assertTelegramSent(fn ($request) => str_ends_with((string) $request->url(), '/editMessageText')
        && $request['chat_id'] === 10
        && $request['message_id'] === 20
        && $request['text'] === 'New');
    expect(telegramCalled('deleteMessage'))->toBeFalse()
        ->and(telegramCalled('sendMessage'))->toBeFalse()
        ->and($meta->fresh()->message_text)->toBe('New')
        ->and(json_encode($meta->fresh()->message_reply_markup))->toContain('X#back');
});

it('survives an in-place edit that Telegram rejects when there is no callback query to answer', function () {
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Same']);

    fakeTelegram(['editMessageText' => 'message is not modified', 'editMessageCaption' => 'there is no caption in the message to edit']);
    $meta->updateAndContinueAction(new TelegramResponse(text: 'Same'));

    expect(telegramCalled('answerCallbackQuery'))->toBeFalse();
});

it('strips the keyboard from a message too old to delete instead of reporting an error', function () {
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Stale']);

    fakeTelegram(['deleteMessage' => "message can't be deleted for everyone"]);
    $meta->deleteMessage();

    $this->assertTelegramSent(fn ($request) => str_ends_with((string) $request->url(), '/editMessageReplyMarkup')
        && ! array_key_exists('reply_markup', $request->data()));
});

it('does nothing further when the message to delete is already gone', function () {
    $meta = MessageMeta::create(['chat_id' => 10, 'message_id' => 20, 'message_text' => 'Gone']);

    fakeTelegram(['deleteMessage' => 'message to delete not found', 'editMessageReplyMarkup' => 'message to edit not found']);
    $meta->deleteMessage();

    expect(telegramCalled('deleteMessage'))->toBeTrue();
});
