<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Jobs\RunIsolatedTask;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->caller = $this->makeBotUser($this->bot, 9101);
    $this->other = $this->makeBotUser($this->bot, 9102);

    wHook()->setBot($this->bot);
    wHook()->setUser($this->caller);
    wHook()->setApi(telegramApi((string) $this->bot->bot_token));
    wHook()->setUpdate(new Update(['update_id' => 1]));
});

function telegramAnswers(int $status, array $body): void
{
    $factory = new Factory;
    $factory->fake(['*' => Http::response($body, $status)]);
    Http::swap($factory);
}

describe('isolated()', function () {
    it('reports success', function () {
        expect(isolated('works', fn () => null))->toBeTrue();
    });

    it('contains a failure, so the next step still runs', function () {
        $ran = false;

        $first = isolated('fails', fn () => throw new RuntimeException('boom'));
        isolated('next', function () use (&$ran) {
            $ran = true;
        });

        expect($first)->toBeFalse()->and($ran)->toBeTrue();
    });

    it('tells nobody by itself, leaving that to onFail', function () {
        $caught = null;

        isolated('fails', fn () => throw new RuntimeException('boom'), onFail: function (Throwable $e) use (&$caught) {
            $caught = $e->getMessage();
        });

        expect($caught)->toBe('boom');
        Http::assertNothingSent();
    });

    it('survives an onFail that fails too', function () {
        $result = isolated('fails', fn () => throw new RuntimeException('boom'), onFail: fn () => throw new RuntimeException('again'));

        expect($result)->toBeFalse();
    });

    it('acts for the given user and puts the caller back, even on failure', function () {
        $seen = null;

        isolated('as other', function () use (&$seen) {
            $seen = wHook()->user()->getKey();
            throw new RuntimeException('boom');
        }, for: $this->other);

        expect($seen)->toBe($this->other->getKey())
            ->and(wHook()->user()->getKey())->toBe($this->caller->getKey());
    });
});

describe('background()', function () {
    it('queues the task with its retries', function () {
        Queue::fake();

        background('later', static fn () => null, tries: 5, backoff: [10, 60]);

        Queue::assertPushed(RunIsolatedTask::class, fn (RunIsolatedTask $job) => $job->tries === 5
            && $job->backoff === [10, 60]
            && $job->displayName() === 'task: later');
    });

    it('does not retry unless asked, because repeating work is not always safe', function () {
        Queue::fake();

        background('once', static fn () => null);

        Queue::assertPushed(RunIsolatedTask::class, fn (RunIsolatedTask $job) => $job->tries === 1);
    });

    it('refuses a closure bound to an object, which would be serialized with it', function () {
        expect(fn () => background('bound', fn () => $this->bot))->toThrow(LogicException::class);
    });

    it('runs with the user, chat and locale restored, and a model reloaded', function () {
        $job = captureTask(function () {
            $other = $this->other;
            background('as other', static function () use ($other) {
                $GLOBALS['task_seen'] = [wHook()->user()->getKey(), $other->getKey(), App::getLocale(), wHook()->conversation()->chat_id];
            }, for: $this->other);
        });

        app()->bind(ResolvesBotLocale::class, fn () => new class implements ResolvesBotLocale
        {
            public function resolve(Bot $bot): string
            {
                return 'fa';
            }
        });

        wHook()->clear();
        App::setLocale('en');

        roundTrip($job)->handle();

        expect($GLOBALS['task_seen'])->toBe([$this->other->getKey(), $this->other->getKey(), 'fa', 9102])
            ->and(App::getLocale())->toBe('en');
    });

    it('stays in the chat it was asked from when no user is given', function () {
        $job = captureTask(function () {
            background('here', static function () {
                $GLOBALS['task_seen'] = [wHook()->user()->getKey(), wHook()->conversation()->chat_id];
            });
        });

        wHook()->clear();
        roundTrip($job)->handle();

        expect($GLOBALS['task_seen'])->toBe([$this->caller->getKey(), 9101]);
    });

    it('leaves a live webhook as it found it when the queue runs it on the spot', function () {
        config(['queue.default' => 'sync']);

        background('sync', static fn () => wHook()->user()->getKey(), for: $this->other);

        expect(wHook()->user()->getKey())->toBe($this->caller->getKey())
            ->and(wHook()->update()->updateId)->toBe(1);
    });

    it('does not throw at the caller when the task fails on the spot', function () {
        config(['queue.default' => 'sync']);

        background('sync fail', static fn () => throw new RuntimeException('boom'));

        expect(wHook()->user()->getKey())->toBe($this->caller->getKey());
    });

    it('never runs when the transaction around it rolls back', function () {
        config(['queue.default' => 'sync']);
        $GLOBALS['task_ran'] = false;

        try {
            DB::transaction(function () {
                background('rolled back', static function () {
                    $GLOBALS['task_ran'] = true;
                });

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        expect($GLOBALS['task_ran'])->toBeFalse();
    });
});

describe('giving up', function () {
    it('reports a task with retries that used them all', function () {
        config(['tbe-essence.bug_report.telegram_chat_id' => 555, 'tbe-essence.logging.telegram_notify' => true]);
        $job = captureTask(function () {
            background('must arrive', static fn () => null, tries: 3);
        });

        $job->failed(new Telegram\Bot\Exceptions\TelegramSDKException('still down'));

        Http::assertSent(fn ($request) => (int) ($request['chat_id'] ?? 0) === 555 && str_contains((string) ($request['text'] ?? ''), 'still down'));
    });

    it('only logs a one-shot task telegram refused', function () {
        config(['tbe-essence.bug_report.telegram_chat_id' => 555, 'tbe-essence.logging.telegram_notify' => true]);
        $job = captureTask(function () {
            background('best effort', static fn () => null);
        });

        $job->failed(new Telegram\Bot\Exceptions\TelegramSDKException('blocked'));

        Http::assertNotSent(fn ($request) => (int) ($request['chat_id'] ?? 0) === 555);
    });
});

describe('notifyUser()', function () {
    it('sends to the private chat of the user', function () {
        expect(notifyUser($this->other, 'hello'))->toBeTrue();

        Http::assertSent(fn ($request) => (int) $request['chat_id'] === 9102 && $request['text'] === 'hello');
    });

    it('returns false instead of throwing when telegram fails', function () {
        telegramAnswers(500, ['ok' => false, 'error_code' => 500, 'description' => 'Internal Server Error']);

        expect(notifyUser($this->other, 'hello'))->toBeFalse();
    });

    it('records a user who blocked the bot', function () {
        telegramAnswers(403, ['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user']);

        notifyUser($this->other, 'hello');

        expect($this->other->refresh()->status)->toBe(BotUser::STATUS_BLOCKED);
    });
});

/** Runs $dispatch against a fake queue and hands back the job it pushed. */
function captureTask(Closure $dispatch): RunIsolatedTask
{
    Queue::fake();
    $dispatch();

    return Queue::pushed(RunIsolatedTask::class)->sole();
}

/** What a worker would get: the job after a trip through the queue payload. */
function roundTrip(RunIsolatedTask $job): RunIsolatedTask
{
    return unserialize(serialize($job));
}
