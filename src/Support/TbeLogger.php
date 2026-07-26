<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use Throwable;

/**
 * PSR-3 logger for the TBE ecosystem.
 *
 * Writes to the channel configured at tbe-essence.logging.channel (the app's
 * default channel when null) and attaches the current webhook identifiers
 * (update_id, update_type, chat_id, user_id, state) plus an optional package
 * tag to every entry.
 *
 * Level convention:
 *  - info: business events (payments, provisioning, campaigns)
 *  - warning: expected user friction or misconfiguration, no trace needed
 *  - error: genuine bugs, always with ['exception' => $e]
 */
class TbeLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(private readonly ?string $package = null)
    {
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        try {
            Log::channel(config('tbe-essence.logging.channel'))
                ->log($level, (string) $message, array_merge($this->defaultContext(), $context));
        } catch (Throwable) {
            // Logging must never break the bot flow.
        }
    }

    private function defaultContext(): array
    {
        $context = $this->package ? ['package' => $this->package] : [];

        try {
            $webhook = wHook();
            if (!$webhook->check()) {
                return $context;
            }

            $update = $webhook->update();

            return array_filter($context + [
                'bot_id' => $webhook->bot()->getKey(),
                'update_id' => $update->updateId,
                'update_type' => $update->objectType(),
                'chat_id' => rescue(fn () => $update->getChat()['id'] ?? null, report: false),
                'user_id' => $webhook->user()->telegramUser?->peer_id,
                'state' => $webhook->requestState(),
            ], fn ($value) => $value !== null);
        } catch (Throwable) {
            return $context;
        }
    }
}
