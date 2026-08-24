<?php

declare(strict_types=1);

use TelegramBotEssentials\Essence\Http\Controllers\TelegramWebhookController;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\Commands\CommandBus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\AdminPanelKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\BotAdminsKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\MainMenuKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;

/*
 * Handlers are registered once per process by the service provider, not on
 * every webhook request by the controller.
 */

it('registers essence built-in reply keys at boot', function () {
    expect(array_keys(app(ReplyKeyBus::class)->getReplyKeys()))
        ->toContain(CancelProcessKey::class, MainMenuKey::class, AdminPanelKey::class, BotAdminsKey::class);
});

it('registers essence built-in commands at boot', function () {
    expect(array_keys(app(CommandBus::class)->getCommands()))->toContain('help');
});

it('registers essence built-in callback queries and state answers at boot', function () {
    expect(app(CallbackQueryBus::class)->getCallbackQueryTypes())->not->toBeEmpty()
        ->and(app(StateAnswerBus::class)->getStateAnswerTypes())->not->toBeEmpty();
});

it('no longer scans handlers from the webhook controller', function () {
    // The scan walked fifteen directories and rebuilt every handler on each
    // request. If this method ever comes back, the boot-time registration
    // below it is redundant and the two will fight over precedence.
    expect(method_exists(
        TelegramWebhookController::class,
        'initializeOptions'
    ))->toBeFalse();
});

it('tolerates an app with no app/Telegram directory', function () {
    // The booted() scan runs in every app, including one that has not
    // created any handlers yet.
    expect(fn () => app()->make(ReplyKeyBus::class))->not->toThrow(Throwable::class);
});
