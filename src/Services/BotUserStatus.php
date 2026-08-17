<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Support\Facades\Event;
use TelegramBotEssentials\Essence\Events\BotUserStatusChanged;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * Tracks whether Telegram will actually deliver to a bot user.
 *
 * Telegram gives us two kinds of signal and neither is sufficient alone:
 *
 *  - push: a my_chat_member update the moment a user blocks or unblocks the
 *    bot. Free and instant, but lossy, since the webhook is (re)set with
 *    drop_pending_updates.
 *  - reactive: the error returned when a send fails. The only way a deleted
 *    account is ever discovered, because Telegram announces nothing when an
 *    account goes away.
 *
 * Both funnel through here so that one transition produces one persisted
 * change and one BotUserStatusChanged event, wherever it was learned.
 */
class BotUserStatus
{
    /**
     * Telegram send failures that say something about the user, as
     * [error_code, description needle, resulting state].
     *
     * Matching is an allowlist on the description and never on the code alone.
     * A 400 is not evidence of an unreachable user: malformed HTML in a
     * broadcast returns 400 "can't parse entities" for every single recipient,
     * and a code-based rule would mark the entire user base unreachable.
     */
    private const FAILURE_SIGNALS = [
        [403, 'bot was blocked by the user', BotUser::STATUS_BLOCKED],
        [403, 'user is deactivated', BotUser::STATUS_DEACTIVATED],
        [403, "bot can't initiate conversation", BotUser::STATUS_UNREACHABLE],
        [400, 'chat not found', BotUser::STATUS_UNREACHABLE],
        [400, 'PEER_ID_INVALID', BotUser::STATUS_UNREACHABLE],
    ];

    /**
     * Map a send failure to a reachability state, or null when the failure says
     * nothing about the user (rate limits, Telegram outages, malformed
     * payloads, network errors). Callers keep their own handling for null.
     */
    public function classify(Throwable $e): ?string
    {
        $code = (int) $e->getCode();
        $description = $e->getMessage();

        foreach (self::FAILURE_SIGNALS as [$signalCode, $needle, $state]) {
            if ($code === $signalCode && stripos($description, $needle) !== false) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Record a failed send. Returns the resulting state, or null when the
     * exception was not a status signal and nothing was changed.
     */
    public function reportFailure(BotUser $botUser, Throwable $e): ?string
    {
        $state = $this->classify($e);

        if ($state === null) {
            return null;
        }

        $this->apply($botUser, $state, BotUserStatusChanged::SOURCE_SEND_FAILURE);

        return $state;
    }

    /**
     * Record a send that worked. Proof of reachability, so it clears whatever
     * we believed before.
     */
    public function reportSuccess(BotUser $botUser): void
    {
        $this->apply($botUser, BotUser::STATUS_ACTIVE, BotUserStatusChanged::SOURCE_SEND_SUCCESS);
    }

    /**
     * The user is reachable again: they unblocked the bot, or an update arrived
     * from them, which they could not have sent while blocking us.
     *
     * This is also the escape hatch for a wrongly recorded deactivation, since
     * an update from an account proves the account exists.
     */
    public function markActive(BotUser $botUser, string $source = BotUserStatusChanged::SOURCE_WEBHOOK): void
    {
        $this->apply($botUser, BotUser::STATUS_ACTIVE, $source);
    }

    public function markBlocked(BotUser $botUser, string $source = BotUserStatusChanged::SOURCE_WEBHOOK): void
    {
        $this->apply($botUser, BotUser::STATUS_BLOCKED, $source);
    }

    /**
     * Persist a state, writing nothing and firing nothing when it did not
     * change. Called on every inbound update, so the common case has to be a
     * read.
     */
    private function apply(BotUser $botUser, string $state, string $source): void
    {
        $from = $botUser->reachability();

        if ($from === $state) {
            return;
        }

        $telegramUser = $botUser->telegramUser;

        if ($state === BotUser::STATUS_DEACTIVATED) {
            if (!$telegramUser) {
                return;
            }

            $telegramUser->deactivated_at = now();
            $telegramUser->save();
        } else {
            if ($state === BotUser::STATUS_ACTIVE && $telegramUser?->deactivated_at) {
                $telegramUser->deactivated_at = null;
                $telegramUser->save();
            }

            if ($botUser->status !== $state) {
                $botUser->status = $state;
                $botUser->save();
            }
        }

        tbeLog('essence')->info('Bot user reachability changed', [
            'bot_user_id' => $botUser->getKey(),
            'peer_id' => $telegramUser?->peer_id,
            'from' => $from,
            'to' => $state,
            'source' => $source,
        ]);

        Event::dispatch(new BotUserStatusChanged($botUser, $from, $state, $source));
    }
}
