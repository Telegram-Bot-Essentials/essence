<?php

namespace TelegramBotEssentials\Essence\Testing;

use Closure;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\HttpFoundation\Response;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\TelegramBotServiceProvider;

/**
 * Shared Testbench base for essence and every companion package. Extend
 * this instead of Orchestra\Testbench\TestCase directly - it wires up the
 * database, the locale, and (via postWebhookUpdate()) a real inbound
 * request through the actual route/middleware/controller stack, not a
 * hand-rolled shortcut.
 *
 * Outbound Telegram API calls are automatically faked: LaravelHttpClient
 * (essence's default http_client_handler) routes every SDK call through
 * Illuminate\Support\Facades\Http, so Http::fake() intercepts them for
 * free - no bespoke SDK mocking needed. Nothing here ever reaches the real
 * Telegram API.
 */
abstract class TestCase extends Orchestra
{
    /** @var array<int, list<string>> abilities granted by grant(), by bot user id */
    private array $grants = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TelegramBotServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        // Pinned rather than left to whatever the booted app defaults to -
        // Workbench boots a real skeleton app with its own env/config, whose
        // default cache store (database) needs a `cache` table nothing here
        // migrates. update_id dedup (Cache::add) needs *some* store.
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.fallback_locale', 'en');
    }

    /**
     * `activated_until` defaults to now() via the migration's useCurrent(),
     * so a bot created without one is immediately treated as expired by
     * TelegramWebhookController - default it far in the future here instead.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeBot(array $attributes = []): Bot
    {
        return Bot::factory()->create(array_merge([
            'activated_until' => now()->addYears(10),
        ], $attributes));
    }

    /**
     * Pre-create a bot's user at a given Telegram peer id, so a subsequent
     * postWebhookUpdate() with the same $peerId finds this row instead of
     * auto-creating a fresh one - pair it with grant() to test a handler
     * that needs an ability.
     *
     * @param  array<string, mixed>  $attributes  bot_users columns, plus
     *                                            state and menu for the private chat
     */
    protected function makeBotUser(Bot $bot, int $peerId, array $attributes = []): BotUser
    {
        TelegramUser::factory()->create(['peer_id' => $peerId]);

        // State and menu live on the conversation; given here they go to
        // the user's private chat, where a test's updates come from.
        $conversation = array_intersect_key($attributes, array_flip(['state', 'menu']));

        $user = BotUser::factory()->create(array_merge([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $peerId,
            'suspend' => false,
        ], array_diff_key($attributes, $conversation)));

        if ($conversation !== []) {
            $user->privateConversation()->update($conversation);
        }

        return $user;
    }

    /**
     * Let $user hold $abilities on the Gate, which on its own denies every
     * ability to everyone. Returns $user, so it wraps makeBotUser().
     */
    protected function grant(BotUser $user, string ...$abilities): BotUser
    {
        if ($this->grants === []) {
            // Answers only for the users and abilities granted here (null
            // otherwise), so the app's own Gate rules still apply.
            Gate::before(fn (mixed $user, string $ability) => $user instanceof BotUser
                && in_array($ability, $this->grantsOf($user), true) ? true : null);
        }

        $this->grants[self::grantKey($user)] = array_values(array_unique([...$this->grantsOf($user), ...$abilities]));

        return $user;
    }

    /** @return list<string> */
    private function grantsOf(BotUser $user): array
    {
        return $this->grants[self::grantKey($user)] ?? [];
    }

    private static function grantKey(BotUser $user): int
    {
        $key = $user->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    /**
     * Send a Telegram update as a real inbound webhook request: routing,
     * TelegramBotAuthentication, and the controller all run for real. A
     * BotUser is auto-created from the update's `from` field exactly like
     * production, so this also exercises that path rather than assuming it.
     *
     * @param  array<string, mixed>  $update
     * @return TestResponse<Response>
     */
    protected function postWebhookUpdate(Bot $bot, array $update): TestResponse
    {
        $update = array_merge(['update_id' => random_int(1, PHP_INT_MAX)], $update);

        return $this->postJson(
            "/api/{$bot->unique_id}/telegram/bot/webhook",
            $update,
            ['x-telegram-bot-api-secret-token' => $bot->secret_token]
        );
    }

    /**
     * A minimal `message` update - a text message from a private chat.
     *
     * @return array<string, mixed>
     */
    protected function makeMessageUpdate(string $text, ?int $peerId = null, ?int $chatId = null): array
    {
        $peerId ??= random_int(100000, 999999999);
        $chatId ??= $peerId;

        return [
            'message' => [
                'message_id' => random_int(1, 999999),
                'date' => now()->timestamp,
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'from' => ['id' => $peerId, 'is_bot' => false, 'first_name' => 'Test'],
                'text' => $text,
            ],
        ];
    }

    /**
     * A minimal `callback_query` update - an inline button press.
     *
     * @return array<string, mixed>
     */
    protected function makeCallbackQueryUpdate(string $callbackData, ?int $peerId = null, ?int $chatId = null): array
    {
        $peerId ??= random_int(100000, 999999999);
        $chatId ??= $peerId;

        return [
            'callback_query' => [
                'id' => (string) random_int(1, 999999),
                'from' => ['id' => $peerId, 'is_bot' => false, 'first_name' => 'Test'],
                'message' => [
                    'message_id' => random_int(1, 999999),
                    'date' => now()->timestamp,
                    'chat' => ['id' => $chatId, 'type' => 'private'],
                ],
                'data' => $callbackData,
            ],
        ];
    }

    /**
     * Move a message or callback_query update into a group chat, and into a
     * forum topic when $topicId is given.
     *
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    protected function inGroupChat(array $update, int $chatId = -100123456, string $type = 'supergroup', ?int $topicId = null): array
    {
        $chat = ['id' => $chatId, 'type' => $type, 'title' => 'A group'];
        $path = isset($update['message']) ? 'message' : 'callback_query.message';

        data_set($update, $path.'.chat', $chat);

        if ($topicId !== null) {
            data_set($update, $path.'.is_topic_message', true);
            data_set($update, $path.'.message_thread_id', $topicId);
        }

        /** @var array<string, mixed> $update */
        return $update;
    }

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

    /** Assert a Telegram Bot API call was made matching $callback - e.g. fn ($request) => $request['text'] === 'hi'. */
    protected function assertTelegramSent(callable $callback): void
    {
        Http::assertSent($callback);
    }

    protected function assertTelegramNotSent(): void
    {
        Http::assertNothingSent();
    }
}
