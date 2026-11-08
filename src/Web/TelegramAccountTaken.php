<?php

namespace TelegramBotEssentials\Essence\Web;

use RuntimeException;

/** A Telegram account belongs to one web account at most. */
class TelegramAccountTaken extends RuntimeException
{
    public static function peer(int $peerId): self
    {
        return new self("Telegram account $peerId is already linked to another web account.");
    }
}
