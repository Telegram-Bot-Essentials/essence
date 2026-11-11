<?php

use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\Label;
use TelegramBotEssentials\Essence\Web\Filament\PanelGroup;
use TelegramBotEssentials\Essence\Web\Filament\PanelMembers;
use TelegramBotEssentials\Essence\Web\PanelCalendar;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.timezone', 'UTC');
});

it('words a label for the bot with its emoji and for the panel without', function () {
    $label = new Label('Wallet credit', '💰', 'heroicon-o-wallet');

    expect($label->forBot())->toBe('💰 Wallet credit')
        ->and($label->forWeb())->toBe('Wallet credit')
        ->and((string) $label)->toBe('💰 Wallet credit')
        ->and(Label::of('Plain')->forBot())->toBe('Plain');
});

it('writes dates in the calendar the viewer chose, Jalali by default in Persian', function () {
    $at = Carbon::parse('2026-11-11 13:20:00', 'UTC');

    app()->setLocale('en');
    expect(PanelCalendar::current())->toBe(PanelCalendar::GREGORIAN)
        ->and(PanelCalendar::format($at))->toBe('Nov 11, 2026 13:20')
        ->and(PanelCalendar::format(null))->toBe('—');

    app()->setLocale('fa');
    expect(PanelCalendar::current())->toBe(PanelCalendar::JALALI)
        ->and(PanelCalendar::format($at, withTime: false))->toContain('۱۴۰۵')->toContain('آبان');

    session()->put(PanelCalendar::SESSION_KEY, PanelCalendar::GREGORIAN);
    expect(PanelCalendar::current())->toBe(PanelCalendar::GREGORIAN);

    app()->setLocale('en');
    session()->put(PanelCalendar::SESSION_KEY, PanelCalendar::JALALI);
    expect(PanelCalendar::format($at, withTime: false))->toBe('20 Aban 1405');
})->skip(fn () => ! class_exists(IntlDateFormatter::class), 'needs ext-intl');

it('takes the default calendar from the config', function () {
    config()->set('tbe-essence.web.calendar', PanelCalendar::JALALI);
    app()->setLocale('en');

    expect(PanelCalendar::current())->toBe(PanelCalendar::JALALI);
});

it('names a member the same way everywhere', function () {
    $member = BotUser::factory()->create();
    $member->telegramUser->update(['first_name' => 'Mina', 'last_name' => null, 'username' => 'mina']);
    $member->refresh();

    expect(PanelMembers::name($member))->toBe('Mina')
        ->and(PanelMembers::username($member))->toBe('@mina')
        ->and(PanelMembers::label($member))->toBe('Mina (@mina)')
        ->and(PanelMembers::name(null))->toBe('?');
});

it('names every sidebar group and calendar in both panel languages', function () {
    foreach (['en', 'fa'] as $locale) {
        $lines = require __DIR__.'/../../lang/'.$locale.'/web.php';

        foreach (PanelGroup::cases() as $group) {
            expect($lines['groups'][$group->value] ?? null)->toBeString();
        }
        foreach (PanelCalendar::available() as $calendar) {
            expect($lines['calendar'][$calendar] ?? null)->toBeString();
        }
    }
});

it('draws a stat card and facts, escaping what it is given', function () {
    $html = Blade::render(<<<'BLADE'
        <x-tbe::facts><x-tbe::fact label="Phone" :value="null" /><x-tbe::fact label="Name" value="<b>x</b>" /></x-tbe::facts>
        <x-tbe::stat-card label="Roles" :value="['Admin', 'Seller']" url="https://example.test/roles" />
        BLADE);

    expect($html)->toContain('Phone')->toContain('—')
        ->toContain('&lt;b&gt;x&lt;/b&gt;')
        ->toContain('href="https://example.test/roles"')
        ->toContain('Admin')->toContain('Seller');
})->skip(fn () => ! class_exists(Panel::class), 'needs Filament');

it('resolves its own panel strings, not their keys', function () {
    app()->setLocale('en');

    expect(__('tbe-essence::web.groups.members'))->toBe('Members')
        ->and(__('tbe-essence::web.login_telegram_hint'))->not->toBe('tbe-essence::web.login_telegram_hint');
});

it('draws list rows and moments in the viewer\'s calendar', function () {
    app()->setLocale('en');
    $html = Blade::render('<x-tbe::row><span>a</span><x-tbe::when :at="$at" /></x-tbe::row><x-tbe::when :at="null" /><x-tbe::when :at="$at" exact /><x-tbe::empty>none</x-tbe::empty>', ['at' => Carbon::parse('2026-11-11 13:20', 'UTC')]);

    expect($html)->toContain('fi-tbe-row')->toContain('title="Nov 11, 2026 13:20"')
        ->toContain('—')->toContain('>Nov 11, 2026 13:20<')->toContain('none');
});
