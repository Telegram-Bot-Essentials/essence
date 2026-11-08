<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Web\WebUser;

/** Lives apart from WebUser so apps without Filament never load its contracts. */
class PanelUser extends WebUser implements FilamentUser, HasName, HasTenants
{
    public function getFilamentName(): string
    {
        $telegramUser = $this->telegramUser;
        $name = $telegramUser instanceof TelegramUser ? $telegramUser->getAttribute('first_name') : null;
        $name = $name ?: $this->getAttribute('telegram_user_peer_id');

        return is_scalar($name) ? (string) $name : '';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return Gate::forUser($this)->allows('web.manage');
    }

    /** A bot user belongs to exactly one bot, so a session never crosses bots. */
    public function canAccessTenant(Model $tenant): bool
    {
        $key = $tenant->getKey();

        return is_numeric($key) && (int) $key === $this->botId();
    }

    /** @return array<Model> */
    public function getTenants(Panel $panel): array
    {
        return PanelBot::query()->whereKey($this->botId())->get()->all();
    }

    private function botId(): int
    {
        $id = $this->getAttribute('bot_id');

        return is_numeric($id) ? (int) $id : 0;
    }
}
