<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Contracts\Auth\Authenticatable;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * A web account: the app's own user model, signed in to the panel. It links
 * to Telegram accounts and acts in a bot as one of their BotUsers. The
 * HasTelegramAccount trait implements all of it except createForTelegram().
 *
 * @see docs/adr/0002-web-accounts-are-the-apps-users.md
 */
interface TelegramAccount extends Authenticatable
{
    /** @return list<int> peer ids in the order they were linked */
    public function linkedPeerIds(): array;

    /** @throws TelegramAccountTaken when another account already links it */
    public function linkTelegram(int $peerId): void;

    public function unlinkTelegram(int $peerId): void;

    /**
     * One BotUser per bot this account may manage, keyed by bot id: the bot's
     * owner if linked, else the first in link order the Gate allows web.manage.
     *
     * @return array<int, BotUser>
     */
    public function panelBotUsers(): array;

    /** Who this account acts as in $bot, or null when it may not manage it. */
    public function actingBotUser(Bot $bot): ?BotUser;

    /**
     * A new account for a Telegram user signing in from /panel with no link
     * yet. The app decides what it stores (a name, no password, ...); essence
     * links the Telegram account afterwards.
     */
    public static function createForTelegram(TelegramUser $telegramUser): static;
}
