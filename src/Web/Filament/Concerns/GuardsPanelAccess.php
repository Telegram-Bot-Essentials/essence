<?php

namespace TelegramBotEssentials\Essence\Web\Filament\Concerns;

use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\Filament\PanelActor;

/**
 * What a handler's $ability and the handler gates do for the bot, for a screen.
 * Checked on every Livewire request, not only on opening: an ability taken away
 * or a user suspended mid-session stops working at the next click.
 */
trait GuardsPanelAccess
{
    abstract public static function requiredAbility(): ?string;

    /** @param  array<string, mixed>  $parameters */
    public static function canAccess(array $parameters = []): bool
    {
        $ability = static::requiredAbility();
        $user = PanelActor::current();

        return $ability === null || ($user instanceof BotUser && Gate::forUser($user)->allows($ability));
    }

    public function bootGuardsPanelAccess(): void
    {
        abort_unless(static::canAccess(), 403);

        try {
            handlerGates()->check($this);
        } catch (FeatureIsDisabled $e) {
            abort(403, $e->getMessage());
        }
    }
}
