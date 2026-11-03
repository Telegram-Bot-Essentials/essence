<?php

namespace TelegramBotEssentials\Essence\Testing;

use Closure;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** For a bot's own tests too: `uses(SweepsTelegramFailures::class)`. */
trait SweepsTelegramFailures
{
    /**
     * Whatever a flow promises should hold however Telegram behaves. Runs $flow
     * once to count its Telegram calls, then again with each call failing in
     * turn, calling $check with the number of the call that failed. The
     * database and cache go back to how they were between runs, so give $check
     * what it needs to look at and do not rely on it surviving the sweep.
     *
     * Failures thrown out of $flow are left to it: wrap the flow in rescue(),
     * or post a webhook update, which does not throw.
     *
     * @param  Closure(): mixed  $flow
     * @param  Closure(int): mixed  $check
     * @return int how many Telegram calls the flow makes
     */
    protected function sweepTelegramFailures(Closure $flow, Closure $check): int
    {
        $calls = $this->inUndoneRun(function () use ($flow) {
            $counter = $this->failingTelegramCall(null);
            $flow();

            return $counter();
        });

        for ($failing = 1; $failing <= $calls; $failing++) {
            $this->inUndoneRun(function () use ($flow, $check, $failing) {
                $this->failingTelegramCall($failing);
                $flow();
                $check($failing);
            });
        }

        return $calls;
    }

    /**
     * Every Telegram call succeeds except the one numbered $failing.
     *
     * @return Closure(): int how many calls have been made so far
     */
    protected function failingTelegramCall(?int $failing): Closure
    {
        /** @var int $calls */
        $calls = 0;

        // A fresh factory: stubs registered first win, and setUp() registered a catch-all.
        $factory = new Factory;
        $factory->fake(function () use (&$calls, $failing) {
            return ++$calls === $failing
                ? Http::response(['ok' => false, 'error_code' => 500, 'description' => 'Internal Server Error'], 500)
                : Http::response(['ok' => true, 'result' => [
                    'message_id' => $calls,
                    'date' => 1,
                    'chat' => ['id' => 1, 'type' => 'private'],
                ]]);
        });
        Http::swap($factory);

        return function () use (&$calls): int {
            return $calls;
        };
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $run
     * @return T
     */
    private function inUndoneRun(Closure $run): mixed
    {
        Cache::flush();

        $connection = DB::connection();
        $managers = app('db.transactions');
        DB::beginTransaction();

        // This transaction is the sweep's own, to undo the run. To the run it
        // does not exist: what waits for a commit, as background() does, has to
        // happen, or the sweep would not see its Telegram calls.
        $level = DB::transactionLevel();
        $runManager = new class($level) extends DatabaseTransactionsManager
        {
            public function __construct(private readonly int $baseLevel)
            {
                parent::__construct();
            }

            public function afterCommitCallbacksShouldBeExecuted($level)
            {
                return $level <= $this->baseLevel;
            }
        };
        // The queue asks the container, a connection its own: both must agree.
        $connection->setTransactionManager($runManager);
        app()->instance('db.transactions', $runManager);

        try {
            return $run();
        } finally {
            $connection->setTransactionManager($managers);
            app()->instance('db.transactions', $managers);
            DB::rollBack();
            wHook()->clear();
            Cache::flush();
        }
    }
}
