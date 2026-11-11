<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Web\Filament\Widgets\NewMembersChart;
use TelegramBotEssentials\Essence\Web\Http\BindWebContext;
use TelegramBotEssentials\Essence\Web\Http\LimitFraming;
use TelegramBotEssentials\Essence\Web\Http\SetPanelLocale;
use TelegramBotEssentials\Essence\Web\PanelCalendar;
use TelegramBotEssentials\Essence\Web\PanelLocales;
use TelegramBotEssentials\Essence\Web\WebAccounts;
use TelegramBotEssentials\Essence\Web\WebPanels;

/** Builds the panel every app shares; packages only add plugins. */
final class WebPanelFactory
{
    public const ID = 'manage';

    public static function make(Panel $panel): Panel
    {
        $panel = $panel
            ->id(self::ID)
            ->path(config()->string('tbe-essence.web.path', 'manage'))
            ->authGuard(WebAccounts::guard())
            // The framework's own sign-in and profile, on the app's account model.
            // No password reset: it needs mail, and /panel signs anyone back in.
            ->login()
            ->profile(AccountProfile::class)
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
            ->tenantMiddleware([BindWebContext::class], isPersistent: true);

        // After the session middleware: the language is kept in the session.
        $panel = self::styled($panel)
            // A /panel link picks its bot; a password sign-in lands on the first one.
            ->tenantMenu(fn (): bool => self::managesSeveralBots())
            ->pages([Dashboard::class, TelegramAccountsPage::class])
            ->navigationGroups(PanelGroup::class)
            ->widgets([AccountWidget::class, NewMembersChart::class])
            ->userMenuItems([
                Action::make('tbe-telegram-accounts')
                    ->label(fn (): string => (string) __('tbe-essence::web.telegram_accounts'))
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->url(fn (): string => TelegramAccountsPage::getUrl())
                    // The profile page is outside any bot, and so is no link to one.
                    ->visible(fn (): bool => Filament::getTenant() !== null),
            ])
            // One account may act as different Telegram accounts per bot: say which.
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): View => view('tbe-essence::web.acting-as'))
            // Most accounts come from Telegram and have no password.
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): Htmlable => new HtmlString(
                '<p style="text-align:center;font-size:.875rem;opacity:.75;">'.e((string) __('tbe-essence::web.login_telegram_hint')).'</p>'
            ))
            ->brandName(fn (): string => self::brand());

        $panels = app(WebPanels::class);
        $panels->markBuilt();

        foreach ($panels->managePlugins() as $plugin) {
            /** @var Plugin $instance */
            $instance = app()->make($plugin);
            $panel = $panel->plugin($instance);
        }

        return $panel;
    }

    /**
     * The look and language menu essence's panel has, for an app's own panels
     * to match: laid out like Filament's demo (no top bar, search and the user
     * menu in a collapsible sidebar, pages as wide as the screen) and in the
     * viewer's chosen language. Call it after the panel's session middleware.
     */
    public static function styled(Panel $panel): Panel
    {
        return $panel
            ->middleware([SetPanelLocale::class], isPersistent: true)
            ->topbar(false)
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->font(fn (): string => app()->getLocale() === 'fa' ? 'Vazirmatn' : 'Inter')
            ->colors(['primary' => Color::Indigo])
            ->favicon(self::FAVICON)
            ->userMenuItems([...self::languageItems(), ...self::calendarItems()])
            ->defaultAvatarProvider(LocalAvatarProvider::class);
    }

    /** A paper plane on indigo, inline so no asset needs publishing. */
    private const FAVICON = 'data:image/svg+xml,'
        .'%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22%3E'
        .'%3Crect width=%2232%22 height=%2232%22 rx=%228%22 fill=%22%234f46e5%22/%3E'
        .'%3Cpath d=%22M7 15.5 24.5 8l-3 16.5-5.5-4.5-3 3v-5l8-7.5-10 6.2z%22 fill=%22white%22/%3E'
        .'%3C/svg%3E';

    /** @return list<Action> the calendar the viewer is not using, to switch to */
    private static function calendarItems(): array
    {
        $items = [];
        foreach (PanelCalendar::available() as $calendar) {
            $items[] = Action::make('tbe-calendar-'.$calendar)
                ->label(fn (): string => (string) __('tbe-essence::web.calendar.switch', ['calendar' => PanelCalendar::name($calendar)]))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(fn (): string => route('tbe.web.calendar', ['calendar' => $calendar]))
                ->visible(fn (): bool => PanelCalendar::current() !== $calendar);
        }

        return $items;
    }

    private static function managesSeveralBots(): bool
    {
        $account = Filament::auth()->user();

        return $account instanceof Model && count(Filament::getUserTenants($account)) > 1;
    }

    /** The bot's @username, its unique id while Telegram cannot be asked. */
    private static function brand(): string
    {
        $bot = Filament::getTenant();
        if (! $bot instanceof Bot) {
            return config()->string('app.name');
        }

        $username = $bot->username();

        return $username !== null ? '@'.$username : $bot->unique_id;
    }

    /** @return list<Action> one per language besides the current one */
    private static function languageItems(): array
    {
        $items = [];
        foreach (PanelLocales::available() as $locale => $name) {
            $items[] = Action::make('tbe-locale-'.$locale)
                ->label($name)
                ->icon(Heroicon::OutlinedLanguage)
                ->url(fn (): string => route('tbe.web.locale', ['locale' => $locale]))
                ->visible(fn (): bool => PanelLocales::current() !== $locale);
        }

        return $items;
    }
}
