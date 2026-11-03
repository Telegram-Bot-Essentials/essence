<?php

namespace TelegramBotEssentials\Essence\Services;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use LogicException;
use ReflectionFunction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Jobs\RunIsolatedTask;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * Runs a piece of work as its own action, so that its failure stays its own.
 * Behind isolated(), background() and notifyUser().
 *
 * Only for what can fail without undoing what came before it: telling someone,
 * or a reaction to something that already happened. Steps that must succeed
 * together belong in a transaction instead.
 */
class IsolatedTasks
{
    /**
     * @param  Closure(Throwable): mixed|null  $onFail
     */
    public function run(string $name, Closure $work, ?BotUser $for = null, ?Closure $onFail = null): bool
    {
        try {
            $for === null ? $work() : wHook()->runForUser($for, $work);

            return true;
        } catch (Throwable $e) {
            $this->report($name, $e);

            if ($onFail !== null) {
                try {
                    $onFail($e);
                } catch (Throwable $handlerFailure) {
                    $this->report($name.' (onFail)', $handlerFailure);
                }
            }

            return false;
        }
    }

    /**
     * @param  list<int>  $backoff  seconds between attempts
     */
    public function dispatch(string $name, Closure $work, ?BotUser $for = null, int $tries = 1, array $backoff = []): void
    {
        if ((new ReflectionFunction($work))->getClosureThis() !== null) {
            throw new LogicException(sprintf(
                'Task "%s" must be a static closure: a bound one would be queued along with the object it belongs to.',
                $name,
            ));
        }

        $task = RunIsolatedTask::capture($name, $work, $for, $tries, $backoff);

        // A rollback takes the task with it: what it reacts to never happened.
        DB::afterCommit(function () use ($name, $task) {
            try {
                app(Dispatcher::class)->dispatch($task);
            } catch (Throwable $e) {
                // The sync driver runs the task here, and any driver can fail
                // to store it. Neither is the caller's problem.
                $this->report($name, $e);
            }
        });
    }

    public function report(string $name, Throwable $e): void
    {
        // Expected outcomes, and Telegram being Telegram: worth a line in the
        // log, not a bug report to the developer.
        if ($e instanceof TelegramSDKException || $e instanceof FeatureIsDisabled || $e instanceof TbeLogicException) {
            tbeLog('essence')->warning('Task "{task}" did not finish: '.$e->getMessage(), ['task' => $name, 'exception' => $e]);

            return;
        }

        tbeLog('essence')->error('Task "{task}" failed', ['task' => $name]);
        exceptionReport($e);
    }
}
