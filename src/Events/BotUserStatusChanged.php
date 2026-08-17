<?php

namespace TelegramBotEssentials\Essence\Events;

use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Fired whenever a user's reachability actually changes.
 *
 * Deliberately not a BotEvent: this also fires from queued sends, where the
 * webhook context belongs to whoever started the broadcast rather than to the
 * user whose status changed, so the affected user is carried explicitly.
 */
class BotUserStatusChanged
{
    /** Learned from a my_chat_member update or an incoming update. */
    public const SOURCE_WEBHOOK = 'webhook';

    /** Learned from the error Telegram returned when we tried to send. */
    public const SOURCE_SEND_FAILURE = 'send_failure';

    /** Learned from a send that succeeded. */
    public const SOURCE_SEND_SUCCESS = 'send_success';

    /**
     * @param string $from previous reachability state
     * @param string $to new reachability state
     * @param string $source one of the SOURCE_* constants
     */
    public function __construct(
        public readonly BotUser $botUser,
        public readonly string  $from,
        public readonly string  $to,
        public readonly string  $source,
    ) {}
}
