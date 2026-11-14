<?php

namespace TelegramBotEssentials\Essence\Web;

/**
 * The panel's own language, apart from the bot's: English unless the viewer
 * picks another of tbe-essence.web.locales. Kept in the session.
 */
final class PanelLocales
{
    public const SESSION_KEY = 'tbe-web.locale';

    /** @return array<string, string> locale => its name in itself */
    public static function available(): array
    {
        $locales = config('tbe-essence.web.locales', ['en' => 'English']);

        return is_array($locales) ? array_filter($locales, fn ($name, $locale): bool => is_string($locale) && is_string($name), ARRAY_FILTER_USE_BOTH) : ['en' => 'English'];
    }

    public static function default(): string
    {
        $locale = config('tbe-essence.web.default_locale', 'en');

        return is_string($locale) && isset(self::available()[$locale]) ? $locale : 'en';
    }

    public static function current(): string
    {
        $chosen = session(self::SESSION_KEY);

        return is_string($chosen) && isset(self::available()[$chosen]) ? $chosen : self::default();
    }
}
