<?php

namespace TelegramBotEssentials\Essence\Web;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use IntlDateFormatter;

/**
 * Which calendar the panel writes dates in: Gregorian or Jalali (Persian).
 * The viewer picks one from the user menu, kept in the session like the
 * language; tbe-essence.web.calendar sets the default, where "auto" means
 * Jalali while the panel speaks Persian.
 */
final class PanelCalendar
{
    public const SESSION_KEY = 'tbe-web.calendar';

    public const GREGORIAN = 'gregorian';

    public const JALALI = 'jalali';

    /** @return list<string> */
    public static function available(): array
    {
        return [self::GREGORIAN, self::JALALI];
    }

    public static function name(string $calendar): string
    {
        return (string) __('tbe-essence::web.calendar.'.$calendar);
    }

    public static function current(): string
    {
        $chosen = session(self::SESSION_KEY);
        if (is_string($chosen) && in_array($chosen, self::available(), true)) {
            return $chosen;
        }

        $default = config('tbe-essence.web.calendar', 'auto');
        if (is_string($default) && in_array($default, self::available(), true)) {
            return $default;
        }

        return app()->getLocale() === 'fa' ? self::JALALI : self::GREGORIAN;
    }

    /** "20 Aban 1405, 13:20" or "Nov 11, 2026 13:20"; a dash when there is no date. */
    public static function format(mixed $at, bool $withTime = true): string
    {
        $at = self::parse($at);
        if ($at === null) {
            return '—';
        }

        $at = $at->copy()->setTimezone(config()->string('app.timezone', 'UTC'));

        if (self::current() !== self::JALALI || ! class_exists(IntlDateFormatter::class)) {
            return $at->translatedFormat($withTime ? 'M j, Y H:i' : 'M j, Y');
        }

        $locale = app()->getLocale() === 'fa' ? 'fa_IR' : 'en_US';
        $formatter = new IntlDateFormatter(
            $locale.'@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $at->getTimezone()->getName(),
            IntlDateFormatter::TRADITIONAL,
            $withTime ? 'd MMMM y، HH:mm' : 'd MMMM y',
        );
        $text = $formatter->format($at->toDateTimeImmutable());

        return is_string($text) ? ($locale === 'fa_IR' ? $text : str_replace('،', ',', $text)) : $at->format('Y-m-d H:i');
    }

    private static function parse(mixed $at): ?CarbonInterface
    {
        if ($at instanceof CarbonInterface) {
            return $at;
        }
        if ($at instanceof DateTimeInterface) {
            return Carbon::instance($at);
        }
        if (is_string($at) && $at !== '') {
            return Carbon::parse($at);
        }
        if (is_int($at)) {
            return Carbon::createFromTimestamp($at);
        }

        return null;
    }
}
