<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use TelegramBotEssentials\Essence\Web\TelegramAccount;

/**
 * Answers for essence's panel, for an account model that may serve other
 * Filament panels too. The app's canAccessPanel(), getTenants() and
 * canAccessTenant() call these when $panel is essence's:
 *
 *     public function canAccessPanel(Panel $panel): bool
 *     {
 *         return $this->isBotPanel($panel) ? $this->canEnterBotPanel() : $this->ownsThePlatform();
 *     }
 *
 * BotPanelOnly does that for a model that serves essence's panel alone.
 *
 * @phpstan-require-implements TelegramAccount
 */
trait EntersBotPanel
{
    public function isBotPanel(Panel $panel): bool
    {
        return $panel->getId() === WebPanelFactory::ID;
    }

    public function canEnterBotPanel(): bool
    {
        return $this->panelBotUsers() !== [];
    }

    /** @return array<Model> */
    public function botPanelTenants(): array
    {
        return PanelBot::query()->whereKey(array_keys($this->panelBotUsers()))->orderBy('id')->get()->all();
    }

    public function canEnterBot(Model $bot): bool
    {
        $key = $bot->getKey();

        return is_numeric($key) && isset($this->panelBotUsers()[(int) $key]);
    }
}
