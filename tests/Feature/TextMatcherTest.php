<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Tests\Fixtures\AdminUrlTextMatcher;
use TelegramBotEssentials\Essence\Tests\Fixtures\SecondUrlTextMatcher;
use TelegramBotEssentials\Essence\Tests\Fixtures\StepwiseStateAnswer;
use TelegramBotEssentials\Essence\Tests\Fixtures\UrlTextMatcher;

uses(RefreshDatabase::class);

beforeEach(function () {
    textMatcherBus()->addTextMatcher(UrlTextMatcher::class);
    stateAnswerBus()->addStateAnswer(StepwiseStateAnswer::class);
    UrlTextMatcher::$handled = [];
    AdminUrlTextMatcher::$handled = [];
    SecondUrlTextMatcher::$handled = [];
    StepwiseStateAnswer::$handled = [];

    $this->bot = $this->makeBot();
    $this->makeBotUser($this->bot, 777);
});

afterEach(fn () => textMatcherBus()->removeTextMatchers([
    UrlTextMatcher::class,
    AdminUrlTextMatcher::class,
    SecondUrlTextMatcher::class,
]));

function urlUpdate(string $text, array $entities = []): array
{
    $update = test()->makeMessageUpdate($text, peerId: 777);
    if ($entities) {
        $update['message']['entities'] = $entities;
    }

    return $update;
}

it('hands text that nothing else claimed to a matching text matcher', function () {
    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1', [
        ['type' => 'url', 'offset' => 0, 'length' => 23],
    ]))->assertOk();

    expect(UrlTextMatcher::$handled)->toBe([['https://example.com/v/1']]);
});

it('reads the url from a text_link entity and counts offsets in UTF-16 units', function () {
    $this->postWebhookUpdate($this->bot, urlUpdate('https://a.co', [
        ['type' => 'text_link', 'offset' => 0, 'length' => 4, 'url' => 'https://hidden.example/x'],
    ]))->assertOk();
    expect(UrlTextMatcher::$handled)->toBe([['https://hidden.example/x']]);

    UrlTextMatcher::$handled = [];
    // The emoji is two UTF-16 units, so the url starts at offset 3 not 2.
    textMatcherBus()->addTextMatcher(new class extends UrlTextMatcher
    {
        protected ?string $pattern = '~.~u';
    });
    $this->postWebhookUpdate($this->bot, urlUpdate('🔗 https://b.co', [
        ['type' => 'url', 'offset' => 3, 'length' => 12],
    ]))->assertOk();
    expect(UrlTextMatcher::$handled)->toBe([['https://b.co']]);
});

it('never lets a text matcher take a message a reply key handles', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('Main Menu 🔰', peerId: 777))->assertOk();

    expect(UrlTextMatcher::$handled)->toBe([]);
});

it('never lets a text matcher take an answer a state answer handles', function () {
    $this->bot->botUsers()->where('telegram_user_peer_id', 777)->sole()
        ->changeState(encodeAnswerState('STEPWISE', 'answer', ['step' => 'text']));

    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    expect(StepwiseStateAnswer::$handled)->toBe(['text'])
        ->and(UrlTextMatcher::$handled)->toBe([]);
});

it('offers text a state answer declined to the text matchers', function () {
    $this->bot->botUsers()->where('telegram_user_peer_id', 777)->sole()
        ->changeState(encodeAnswerState('STEPWISE', 'answer', ['step' => 'photo']));

    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    expect(StepwiseStateAnswer::$handled)->toBe([])
        ->and(UrlTextMatcher::$handled)->toHaveCount(1);
});

it('falls through to the invalid-request reply when no matcher matches', function () {
    $this->postWebhookUpdate($this->bot, urlUpdate('just words'))->assertOk();

    expect(UrlTextMatcher::$handled)->toBe([]);
    $this->assertTelegramSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), __('tbe::general.alerts.requestIsInvalid')));
});

it('does not send the invalid-request reply once a matcher handled the message', function () {
    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    Http::assertNotSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), __('tbe::general.alerts.requestIsInvalid')));
});

it('reads a url from the caption of a media message', function () {
    $update = test()->makeMessageUpdate('', peerId: 777);
    unset($update['message']['text']);
    $update['message']['photo'] = [['file_id' => 'f', 'file_unique_id' => 'u', 'width' => 1, 'height' => 1]];
    $update['message']['caption'] = 'look https://example.com/p/9';
    $update['message']['caption_entities'] = [['type' => 'url', 'offset' => 5, 'length' => 23]];

    textMatcherBus()->removeTextMatcher(UrlTextMatcher::class);
    textMatcherBus()->addTextMatcher(new class extends UrlTextMatcher
    {
        protected ?string $pattern = '~https?://~i';
    });
    $this->postWebhookUpdate($this->bot, $update)->assertOk();

    expect(UrlTextMatcher::$handled)->toBe([['https://example.com/p/9']]);
});

it('lets a matcher judge a message that has no text at all', function () {
    $update = test()->makeMessageUpdate('', peerId: 777);
    unset($update['message']['text']);
    $update['message']['photo'] = [['file_id' => 'f', 'file_unique_id' => 'u', 'width' => 1, 'height' => 1]];

    $this->postWebhookUpdate($this->bot, $update)->assertOk();

    expect(UrlTextMatcher::$handled)->toBe([]);
    $this->assertTelegramSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), __('tbe::general.alerts.requestIsInvalid')));
});

it('hands the message to the first registered matcher that matches, and only that one', function () {
    textMatcherBus()->addTextMatcher(SecondUrlTextMatcher::class);

    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    expect(UrlTextMatcher::$handled)->toHaveCount(1)
        ->and(SecondUrlTextMatcher::$handled)->toBe([]);
});

it('skips a matcher the user has no access to and tries the next one', function () {
    textMatcherBus()->removeTextMatcher(UrlTextMatcher::class);
    textMatcherBus()->addTextMatcher(AdminUrlTextMatcher::class);
    textMatcherBus()->addTextMatcher(UrlTextMatcher::class);

    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    expect(AdminUrlTextMatcher::$handled)->toBe([])
        ->and(UrlTextMatcher::$handled)->toHaveCount(1);
});

it('falls through to the invalid-request reply when the only matcher is off limits', function () {
    textMatcherBus()->removeTextMatcher(UrlTextMatcher::class);
    textMatcherBus()->addTextMatcher(AdminUrlTextMatcher::class);

    $this->postWebhookUpdate($this->bot, urlUpdate('https://example.com/v/1'))->assertOk();

    expect(AdminUrlTextMatcher::$handled)->toBe([]);
    $this->assertTelegramSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), __('tbe::general.alerts.requestIsInvalid')));
});
