<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use TelegramBotEssentials\Essence\TelegramBotServiceProvider;

uses(RefreshDatabase::class);

// The routing tests below start from no routes; the shipped defaults are
// covered by the channel registration tests at the end.
beforeEach(function () {
    config()->set('tbe-essence.logging.channels', []);
    config()->set('tbe-essence.logging.audit_channel', null);
});

function tbeTestChannel(string $name): TestHandler
{
    config()->set("logging.channels.$name", ['driver' => 'monolog', 'handler' => TestHandler::class]);

    /** @var TestHandler */
    return Log::channel($name)->getLogger()->getHandlers()[0];
}

it('prefixes the message with the subject from the context', function () {
    $handler = tbeTestChannel('tbe_main');
    config()->set('tbe-essence.logging.channel', 'tbe_main');

    tbeLog('billing')->info('Invoice paid', ['bot_id' => 3, 'user_id' => 12345, 'username' => 'alice']);

    $record = $handler->getRecords()[0];
    expect($record->message)->toBe('bot#3 user#12345 @alice | Invoice paid')
        ->and($record->context)->toMatchArray(['package' => 'billing', 'bot_id' => 3]);
});

it('leaves the message alone when there is no subject', function () {
    $handler = tbeTestChannel('tbe_main');
    config()->set('tbe-essence.logging.channel', 'tbe_main');

    tbeLog('billing')->info('Marked overdue invoices as failed');

    expect($handler->getRecords()[0]->message)->toBe('Marked overdue invoices as failed');
});

it('routes a package to its own channel and the rest to the shared one', function () {
    $main = tbeTestChannel('tbe_main');
    $billing = tbeTestChannel('tbe_billing');
    config()->set('tbe-essence.logging.channel', 'tbe_main');
    config()->set('tbe-essence.logging.channels', ['billing' => 'tbe_billing']);

    tbeLog('billing')->info('to billing');
    tbeLog('settings')->info('to main');

    expect($billing->hasInfoThatContains('to billing'))->toBeTrue()
        ->and($main->hasInfoThatContains('to main'))->toBeTrue()
        ->and($main->hasInfoThatContains('to billing'))->toBeFalse();
});

it('sends audit entries to the audit channel, flagged', function () {
    $main = tbeTestChannel('tbe_main');
    $audit = tbeTestChannel('tbe_audit');
    config()->set('tbe-essence.logging.channel', 'tbe_main');
    config()->set('tbe-essence.logging.audit_channel', 'tbe_audit');

    tbeLog('user-management')->audit('User role changed', ['target_user_id' => 7]);

    expect($main->getRecords())->toBe([])
        ->and($audit->getRecords()[0]->context)->toMatchArray(['audit' => true, 'target_user_id' => 7]);
});

it('falls back to the package channel for audit entries when no audit channel is set', function () {
    $main = tbeTestChannel('tbe_main');
    config()->set('tbe-essence.logging.channel', 'tbe_main');

    tbeLog('settings')->audit('Bot setting updated');

    expect($main->hasInfoThatContains('Bot setting updated'))->toBeTrue();
});

it('takes the subject from the bot user it is bound to', function () {
    $handler = tbeTestChannel('tbe_main');
    config()->set('tbe-essence.logging.channel', 'tbe_main');
    $bot = $this->makeBot();
    $botUser = $this->makeBotUser($bot, 4242);
    $botUser->telegramUser->update(['username' => 'bob']);

    tbeLog('billing')->for($botUser)->info('Invoice #7 paid');
    tbeLog('billing')->info('unbound');

    expect($handler->getRecords()[0]->message)->toBe('bot#'.$bot->id.' user#4242 @bob | Invoice #7 paid')
        ->and($handler->getRecords()[1]->message)->toBe('unbound');
});

it('fills placeholders from the context and leaves unknown ones alone', function () {
    $handler = tbeTestChannel('tbe_main');
    config()->set('tbe-essence.logging.channel', 'tbe_main');

    tbeLog('billing')->info('Invoice #{invoice_id} paid: {price}, {missing} {payload}', [
        'invoice_id' => 12,
        'price' => 250000,
        'payload' => ['x' => 1],
    ]);

    expect($handler->getRecords()[0]->message)->toBe('Invoice #12 paid: 250000, {missing} {payload}');
});

it('never throws over a malformed channel map', function () {
    config()->set('tbe-essence.logging.channels', 'not-a-map');

    tbeLog('billing')->info('still fine');
    tbeLog('billing')->audit('still fine');
})->throwsNoExceptions();

it('defines the channels it routes to as daily files', function () {
    expect(config('logging.channels.billing'))->toMatchArray([
        'driver' => 'daily',
        'path' => storage_path('logs/billing.log'),
        'days' => 90,
    ])
        ->and(config('logging.channels.admin-audit.days'))->toBe(90)
        ->and(config('logging.channels.activity.days'))->toBe(14)
        ->and(config('logging.channels.essence.path'))->toBe(storage_path('logs/essence.log'));
});

it('leaves a channel the app defines alone', function () {
    config()->set('logging.channels.billing', ['driver' => 'single', 'path' => '/tmp/app-billing.log']);
    config()->set('tbe-essence.logging.channels', ['billing' => 'billing', 'announcements' => 'broadcasts']);

    (fn () => $this->registerLogChannels())->call(app()->getProvider(TelegramBotServiceProvider::class));

    expect(config('logging.channels.billing'))->toBe(['driver' => 'single', 'path' => '/tmp/app-billing.log'])
        ->and(config('logging.channels.broadcasts.driver'))->toBe('daily');
});

it('merges an app logging block over the defaults instead of replacing them', function () {
    config()->set('tbe-essence.logging', [
        'channels' => ['orders' => 'billing', 'settings' => 'app_settings'],
        'retention_days' => ['activity' => 30],
    ]);

    (fn () => $this->mergeLoggingConfig())->call(app()->getProvider(TelegramBotServiceProvider::class));

    expect(config('tbe-essence.logging.channels'))->toMatchArray([
        'orders' => 'billing',
        'settings' => 'app_settings',
        'billing' => 'billing',
        'user-management' => 'activity',
    ])
        ->and(config('tbe-essence.logging.retention_days'))->toBe(['billing' => 90, 'admin-audit' => 90, 'activity' => 30])
        ->and(config('tbe-essence.logging.channel'))->toBe('essence')
        ->and(config('tbe-essence.logging.audit_channel'))->toBe('admin-audit');
});
