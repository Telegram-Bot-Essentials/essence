<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TelegramBotEssentials\Essence\Exceptions\HandlerContextExpired;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Tests\Fixtures\ResumingStateAnswer;

uses(RefreshDatabase::class);

it('resolves the message meta while the row still exists', function () {
    $meta = MessageMeta::create(['chat_id' => 1, 'message_id' => 1]);

    $answer = new ResumingStateAnswer;
    $answer->setParams(['message_meta_id' => $meta->id]);

    expect($answer->continueFlow()->is($meta))->toBeTrue();
});

it('raises HandlerContextExpired once the message meta has been pruned out from under it', function () {
    $meta = MessageMeta::create(['chat_id' => 1, 'message_id' => 1]);
    $prunedId = $meta->id;
    $meta->delete();

    $answer = new ResumingStateAnswer;
    $answer->setParams(['message_meta_id' => $prunedId]);

    expect(fn () => $answer->continueFlow())->toThrow(HandlerContextExpired::class);
});

it('turns HandlerContextExpired into an expiry notice and clears the stuck state', function () {
    $bot = $this->makeBot();
    $this->makeBotUser($bot, 555, ['state' => 'SOMEFLOW-continue']);

    // A real inbound request populates wHook() for the rest of the test.
    $this->postWebhookUpdate($bot, $this->makeCallbackQueryUpdate('UNREGISTERED-x', peerId: 555))->assertOk();

    Http::fake();
    exceptionHandler()->handle(new HandlerContextExpired);

    $this->assertTelegramSent(
        fn ($request) => str_contains((string) $request->url(), '/answerCallbackQuery')
            && str_contains((string) $request['text'], __('tbe::general.alerts.contextExpired'))
    );

    expect($bot->botUsers()->where('telegram_user_peer_id', 555)->sole()->state)->toBeNull();
});

it('does not recurse into itself when it fails with no webhook context', function () {
    // Fresh app: wHook() has no bot/user/update/api, so the fallback
    // notification path must be skipped entirely rather than dereferenced.
    expect(fn () => exceptionHandler()->handle(new RuntimeException('boom')))
        ->toThrow(HttpException::class);

    Http::assertNothingSent();
});
