<?php

namespace TelegramBotEssentials\Essence\Testing;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Foundation\Auth\User;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Web\Filament\BotPanelOnly;
use TelegramBotEssentials\Essence\Web\HasTelegramAccount;
use TelegramBotEssentials\Essence\Web\TelegramAccount;

/** A web account as an app's user model would be, for WebTestCase. */
class TestAccount extends User implements FilamentUser, HasTenants, TelegramAccount
{
    use BotPanelOnly;
    use HasTelegramAccount;

    protected $table = 'test_accounts';

    protected $guarded = ['id'];

    public static function createForTelegram(TelegramUser $telegramUser): static
    {
        return static::query()->create(['name' => $telegramUser->first_name ?: (string) $telegramUser->peer_id]);
    }
}
