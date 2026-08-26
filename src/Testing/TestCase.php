<?php

namespace TelegramBotEssentials\Essence\Testing;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\HttpFoundation\Response;
use TelegramBotEssentials\Essence\Enums\Roles;
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
     * auto-creating a fresh member-level one - the only way to test a
     * role-gated handler (e.g. an admin-only ReplyKey).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeBotUser(Bot $bot, int $peerId, array $attributes = []): BotUser
    {
        TelegramUser::factory()->create(['peer_id' => $peerId]);

        return BotUser::factory()->create(array_merge([
            'bot_id' => $bot->id,
            'telegram_user_peer_id' => $peerId,
            'power' => Roles::MEMBER->value,
            'state' => null,
            'suspend' => false,
        ], $attributes));
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

    /** Assert a Telegram Bot API call was made matching $callback - e.g. fn ($request) => $request['text'] === 'hi'. */
    protected function assertTelegramSent(callable $callback): void
    {
        Http::assertSent($callback);
    }
}
