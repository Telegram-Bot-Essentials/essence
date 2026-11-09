<?php

namespace TelegramBotEssentials\Essence\Web\Filament\Concerns;

use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\Filament\PanelActor;

/**
 * A package's panel widget, shown only to who may use the screen it summarises:
 * `protected static ?string $ability = 'billing.invoices.manage';`. Checked
 * again on every Livewire request the widget makes.
 */
trait GuardsWidget
{
    public static function canView(): bool
    {
        $ability = static::$ability ?? null;
        $user = PanelActor::current();

        return $ability === null || ($user instanceof BotUser && Gate::forUser($user)->allows($ability));
    }

    public function bootGuardsWidget(): void
    {
        abort_unless(static::canView(), 403);
    }
}
