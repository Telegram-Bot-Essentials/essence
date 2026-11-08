<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Web\Http\BindWebContext;
use TelegramBotEssentials\Essence\Web\Http\LimitFraming;
use TelegramBotEssentials\Essence\Web\WebPanels;

/** Builds the panel every app shares; packages only add plugins. */
final class WebPanelFactory
{
    public static function make(Panel $panel): Panel
    {
        $panel = $panel
            ->id('manage')
            ->path(config()->string('tbe-essence.web.path', 'manage'))
            ->authGuard('tbe-web')
            ->tenant(PanelBot::class, slugAttribute: 'unique_id')
            ->spa()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LimitFraming::class,
            ])
            // Persistent, in this order: Livewire's update requests must sign the user
            // in and bind the bot again, or actions outlive the session.
            ->authMiddleware([PanelAuthenticate::class], isPersistent: true)
            ->tenantMiddleware([BindWebContext::class], isPersistent: true)
            ->tenantMenu(false)
            ->defaultAvatarProvider(LocalAvatarProvider::class)
            ->brandName(fn (): string => Filament::getTenant() instanceof Bot ? Filament::getTenant()->unique_id : config()->string('app.name'));

        $panels = app(WebPanels::class);
        $panels->markBuilt();

        foreach ($panels->managePlugins() as $plugin) {
            /** @var Plugin $instance */
            $instance = app()->make($plugin);
            $panel = $panel->plugin($instance);
        }

        return $panel;
    }
}
