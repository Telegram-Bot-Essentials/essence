<?php

use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Testing\TestAccount;
use TelegramBotEssentials\Essence\Web\Filament\AccountProfile;
use TelegramBotEssentials\Essence\Web\Filament\TelegramAccountsPage;
use TelegramBotEssentials\Essence\Web\WebAccounts;

uses(RefreshDatabase::class);

/** A bot whose member $peer may manage it in the panel. */
function passwordBot(string $uniqueId, int $peer): array
{
    $owner = TelegramUser::factory()->create();
    $bot = Bot::factory()->create(['unique_id' => $uniqueId, 'bot_token' => "1:$uniqueId", 'bot_owner_peer_id' => $owner->peer_id, 'activated_until' => now()->addYear()]);
    TelegramUser::query()->firstOrCreate(['peer_id' => $peer], TelegramUser::factory()->raw(['peer_id' => $peer]));

    return [$bot, BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => $peer])];
}

beforeEach(function () {
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    [$this->bot, $this->member] = passwordBot('alpha', 5101);
    $this->account = $this->panelAccount($this->member);
});

function signInForm(): Testable
{
    Filament::setCurrentPanel('manage');

    return Livewire::test(Login::class);
}

it('offers the password form beside /panel', function () {
    $this->get('/alpha/web/open')->assertOk()->assertSee(__('tbe-essence::web.password_sign_in'))->assertSee('/manage/login');
    $this->get('/manage/login')->assertOk()->assertSee(__('tbe-essence::web.login_telegram_hint'));
});

it('sends signed-out visitors outside a bot to the password form', function () {
    $this->get('/manage/profile')->assertRedirect('/manage/login');
});

it('signs in with email and password', function () {
    $this->account->update(['email' => 'owner@example.test', 'password' => Hash::make('a-long-secret')]);

    signInForm()->fillForm(['email' => 'owner@example.test', 'password' => 'a-long-secret'])->call('authenticate')->assertHasNoFormErrors();

    expect(Auth::guard(WebAccounts::guard())->id())->toBe($this->account->id);
    $this->get('/manage')->assertRedirect('/manage/alpha');
});

it('refuses a wrong password', function () {
    $this->account->update(['email' => 'owner@example.test', 'password' => Hash::make('a-long-secret')]);

    signInForm()->fillForm(['email' => 'owner@example.test', 'password' => 'wrong'])->call('authenticate')->assertHasFormErrors(['email']);

    expect(Auth::guard(WebAccounts::guard())->check())->toBeFalse();
});

it('refuses an account that manages no bot', function () {
    TestAccount::query()->create(['name' => 'Nobody', 'email' => 'nobody@example.test', 'password' => Hash::make('a-long-secret')]);

    signInForm()->fillForm(['email' => 'nobody@example.test', 'password' => 'a-long-secret'])->call('authenticate')->assertHasFormErrors(['email']);

    expect(Auth::guard(WebAccounts::guard())->check())->toBeFalse();
});

it('sets a first password on an account from telegram without a current one', function () {
    $this->enterPanel('manage', $this->bot, $this->account);

    Livewire::test(AccountProfile::class)
        ->assertFormFieldHidden('currentPassword')
        ->fillForm(['email' => 'owner@example.test', 'password' => 'a-long-secret', 'passwordConfirmation' => 'a-long-secret'])
        ->call('save')
        ->assertHasNoFormErrors();

    $account = $this->account->fresh();
    expect($account->email)->toBe('owner@example.test')
        ->and(Hash::check('a-long-secret', $account->password))->toBeTrue();

    // Now the password is a way in, the last Telegram account may go.
    $this->enterPanel('manage', $this->bot, $account);
    Livewire::test(TelegramAccountsPage::class)->assertTableActionVisible('unlink', $account->telegramLinks()->firstOrFail());
});

it('asks for the current password before changing one', function () {
    $this->account->update(['email' => 'owner@example.test', 'password' => Hash::make('a-long-secret')]);
    $this->enterPanel('manage', $this->bot, $this->account->fresh());

    Livewire::test(AccountProfile::class)
        ->fillForm(['password' => 'another-long-secret', 'passwordConfirmation' => 'another-long-secret'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    Livewire::test(AccountProfile::class)
        ->fillForm(['password' => 'another-long-secret', 'passwordConfirmation' => 'another-long-secret', 'currentPassword' => 'a-long-secret'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('another-long-secret', $this->account->fresh()->password))->toBeTrue();
});

it('keeps an email to one account', function () {
    TestAccount::query()->create(['name' => 'Other', 'email' => 'taken@example.test']);
    $this->enterPanel('manage', $this->bot, $this->account);

    Livewire::test(AccountProfile::class)
        ->fillForm(['email' => 'taken@example.test'])
        ->call('save')
        ->assertHasFormErrors(['email']);
});

it('hides the bot switcher from an account with one bot', function () {
    $this->enterPanel('manage', $this->bot, $this->account);

    expect(Filament::getCurrentPanel()->hasTenantMenu())->toBeFalse();
});

it('shows the bot switcher to an account with several bots', function () {
    passwordBot('beta', 5101);
    // A request loads the account afresh; this one has its bots in memory.
    $this->enterPanel('manage', $this->bot, $this->account->fresh());

    expect(Filament::getCurrentPanel()->hasTenantMenu())->toBeTrue();
});

it('opens the profile page over a real request', function () {
    Auth::guard(WebAccounts::guard())->login($this->account);

    $this->get('/manage/profile')->assertOk()->assertSee(__('filament-panels::auth/pages/edit-profile.form.email.label'));
});

it('refuses profile updates once signed out', function () {
    Auth::guard(WebAccounts::guard())->login($this->account);

    $this->livewireRequest('/manage/profile', 'AccountProfile', 'save', [],
        fn () => Auth::guard(WebAccounts::guard())->logout())->assertUnauthorized();
});
