<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\TelegramAccount;
use TelegramBotEssentials\Essence\Web\WebAccounts;

/**
 * The BotUser a panel request acts as. Abilities are always asked with it,
 * never with the signed-in account: Gate rules answer for BotUsers.
 */
final class PanelActor
{
    public static function current(): ?BotUser
    {
        $account = self::account();
        $bot = Filament::getTenant();

        return $account instanceof TelegramAccount && $bot instanceof Bot ? $account->actingBotUser($bot) : null;
    }

    /** Mixed on purpose: the guard's model is the app's, whatever the app's default user is. */
    private static function account(): mixed
    {
        return Auth::guard(WebAccounts::guard())->user();
    }
}
