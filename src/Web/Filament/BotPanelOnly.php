<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use TelegramBotEssentials\Essence\Web\TelegramAccount;

/**
 * FilamentUser and HasTenants for an account model that serves only essence's
 * panel. A model with panels of its own uses EntersBotPanel instead.
 *
 * @phpstan-require-implements TelegramAccount
 */
trait BotPanelOnly
{
    use EntersBotPanel;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isBotPanel($panel) && $this->canEnterBotPanel();
    }

    /** @return array<Model> */
    public function getTenants(Panel $panel): array
    {
        return $this->isBotPanel($panel) ? $this->botPanelTenants() : [];
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->canEnterBot($tenant);
    }
}
