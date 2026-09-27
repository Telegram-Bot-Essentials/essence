<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use Throwable;

/**
 * PSR-3 logger for the TBE ecosystem.
 *
 * Writes to the channel configured for the package at
 * tbe-essence.logging.channels.<package>, falling back to
 * tbe-essence.logging.channel (the app's default channel when null). audit()
 * entries go to tbe-essence.logging.audit_channel instead, so what admins did
 * is kept apart from operational noise.
 *
 * Every entry carries the common keys below: from the current webhook when
 * there is one, from for($botUser) when the caller knows whose entry it is
 * better than the webhook does (a queued job, a gateway callback), and from
 * the call's own context, in that order of precedence.
 *  - package: the tag passed to tbeLog()
 *  - bot_id, user_id (Telegram peer id), username, chat_id
 *  - update_id, update_type, state
 * Domain identifiers are <model>_id (invoice_id, service_id, ...).
 *
 * PSR-3 placeholders in the message are filled from the context
 * ("Invoice #{invoice_id} paid" -> "Invoice #12 paid"), and the message is
 * prefixed with a subject built from bot_id, user_id and username
 * ("bot#3 user#12345 @alice | Invoice #12 paid"), so a line says whose it is
 * and what happened without expanding its context.
 *
 * Level convention:
 *  - debug: per-update tracing, off in production
 *  - info: business events (payments, provisioning, campaigns)
 *  - warning: expected user friction or misconfiguration, no trace needed
 *  - error: genuine bugs, always with ['exception' => $e]
 */
class TbeLogger implements LoggerInterface
{
    use LoggerTrait;

    /** @var array<string, mixed> */
    private array $bound = [];

    public function __construct(private readonly ?string $package = null) {}

    /**
     * A logger whose entries are about $botUser: its bot_id, user_id and
     * username replace the webhook's. Takes whatever a relation returned,
     * since logging must never throw: anything but a BotUser binds nothing.
     */
    public function for(mixed $botUser): self
    {
        $logger = clone $this;
        if (! $botUser instanceof BotUser) {
            return $logger;
        }

        $telegramUser = rescue(fn () => $botUser->telegramUser, report: false);
        $logger->bound = array_filter([
            'bot_id' => $botUser->getAttribute('bot_id'),
            'user_id' => $botUser->getAttribute('telegram_user_peer_id'),
            'username' => $telegramUser instanceof TelegramUser ? $telegramUser->username : null,
        ], fn ($value) => $value !== null);

        return $logger;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->write($this->channel(), $level, $message, $context);
    }

    /**
     * Something an admin or the owner did (a role change, a setting, a manual
     * payment decision), kept on the audit channel.
     *
     * @param  array<string, mixed>  $context
     */
    public function audit(string|Stringable $message, array $context = []): void
    {
        $this->write(
            $this->auditChannel() ?? $this->channel(),
            'info',
            $message,
            $context + ['audit' => true],
        );
    }

    /** @param  array<mixed>  $context */
    private function write(?string $channel, mixed $level, string|Stringable $message, array $context): void
    {
        try {
            $context = array_merge($this->defaultContext(), $this->bound, $context);

            Log::channel($channel)->log($level, $this->subject($context).$this->interpolate((string) $message, $context), $context);
        } catch (Throwable) {
            // Logging must never break the bot flow.
        }
    }

    private function channel(): ?string
    {
        $channels = config('tbe-essence.logging.channels');
        $channel = $this->package !== null && is_array($channels) ? ($channels[$this->package] ?? null) : null;
        $channel ??= config('tbe-essence.logging.channel');

        return is_string($channel) ? $channel : null;
    }

    private function auditChannel(): ?string
    {
        $channel = config('tbe-essence.logging.audit_channel');

        return is_string($channel) ? $channel : null;
    }

    /** @param  array<mixed>  $context */
    private function interpolate(string $message, array $context): string
    {
        if (! str_contains($message, '{')) {
            return $message;
        }

        return (string) preg_replace_callback('/\{([\w.]+)\}/', function (array $match) use ($context): string {
            $value = $context[$match[1]] ?? null;

            return match (true) {
                $value === null => $match[0],
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value), $value instanceof Stringable => (string) $value,
                default => $match[0],
            };
        }, $message);
    }

    /** @param  array<mixed>  $context */
    private function subject(array $context): string
    {
        $part = fn (string $key, string $prefix): ?string => is_scalar($context[$key] ?? null) && $context[$key] !== ''
            ? $prefix.$context[$key]
            : null;

        $parts = array_filter([
            $part('bot_id', 'bot#'),
            $part('user_id', 'user#'),
            $part('username', '@'),
        ]);

        return $parts === [] ? '' : implode(' ', $parts).' | ';
    }

    private function defaultContext(): array
    {
        $context = $this->package ? ['package' => $this->package] : [];

        try {
            $webhook = wHook();
            if (! $webhook->check()) {
                return $context;
            }

            $update = $webhook->update();
            $telegramUser = $webhook->user()->telegramUser;
            $telegramUser = $telegramUser instanceof TelegramUser ? $telegramUser : null;

            return array_filter($context + [
                'bot_id' => $webhook->bot()->getKey(),
                'user_id' => $telegramUser?->peer_id,
                'username' => $telegramUser?->username,
                'chat_id' => rescue(fn () => $update->getChat()['id'] ?? null, report: false),
                'update_id' => $update->updateId,
                'update_type' => $update->objectType(),
                'state' => answerStateSummary($webhook->requestState()),
            ], fn ($value) => $value !== null);
        } catch (Throwable) {
            return $context;
        }
    }
}
