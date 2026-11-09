<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Testing\TestAccount;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\LatePlugin;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\LockedPage;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\RefusingGate;
use TelegramBotEssentials\Essence\Web\Filament\Widgets\NewMembersChart;
use TelegramBotEssentials\Essence\Web\LoginLinks;
use TelegramBotEssentials\Essence\Web\TelegramAccountTaken;
use TelegramBotEssentials\Essence\Web\WebAccounts;

uses(RefreshDatabase::class);

function webBot(string $uniqueId = 'alpha', int $peer = 111): array
{
    $owner = TelegramUser::factory()->create();
    $bot = Bot::factory()->create(['unique_id' => $uniqueId, 'bot_token' => "1:$uniqueId", 'bot_owner_peer_id' => $owner->peer_id, 'activated_until' => now()->addYear()]);
    TelegramUser::query()->firstOrCreate(['peer_id' => $peer], TelegramUser::factory()->raw(['peer_id' => $peer]));
    $user = BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => $peer]);

    return [$bot, $user];
}

function grantManage(): void
{
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
}

it('sends signed-out visitors to the bot sign-in page', function () {
    webBot();

    $this->get('/manage/alpha/buy')->assertRedirect('/alpha/web/open');
});

it('keeps a session on its own bot', function () {
    grantManage();
    [, $user] = webBot();
    webBot('beta', 222);

    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/manage/alpha/buy')->assertOk()->assertSee('Buy service');
    $this->get('/manage/beta/buy')->assertNotFound();
});

it('keeps the manage panel behind an ability', function () {
    [, $user] = webBot();
    $account = $this->panelAccount($user);
    $this->actingAs($account, 'tbe-web');

    $this->get('/manage/alpha/ticket')->assertForbidden();

    // Each real request loads the account afresh; it keeps its bots for one.
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->actingAs($account->fresh(), 'tbe-web');
    $this->get('/manage/alpha/ticket')->assertOk()->assertSee('Ticket #42');
});

it('spends a login link on POST only, once', function () {
    [, $user] = webBot();
    $url = app(LoginLinks::class)->create($user);
    $path = parse_url($url, PHP_URL_PATH);

    $this->get($path)->assertOk();
    $this->get($path)->assertOk();

    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->post($path)->assertRedirect(url('manage/alpha'));
    expect(Auth::guard('tbe-web')->user()->linkedPeerIds())->toBe([111]);

    Auth::guard('tbe-web')->logout();
    $this->post($path)->assertStatus(410);
    $this->get($path)->assertStatus(410);
});

it('lists only the current bot\'s users in a stock Filament table', function () {
    [, $user] = webBot();
    webBot('beta', 222);
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/manage/alpha/bot-users')->assertOk()->assertSee('111')->assertDontSee('222');
});

it('has tenant, bot, api and user on a real Livewire update request', function () {
    grantManage();
    [, $user] = webBot();
    webBot('beta', 222);
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $html = $this->get('/manage/alpha/buy')->assertOk()->getContent();
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $m);
    $snapshot = collect($m[1])->map(fn ($s) => html_entity_decode($s))->first(fn ($s) => str_contains($s, 'paidContext'));

    tenancy()->end();
    app()->forgetScopedInstances();

    $response = $this->withHeader('X-Livewire', '1')
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson(app('livewire')->getUpdateUri(), [
            '_token' => csrf_token(),
            'components' => [['snapshot' => $snapshot, 'updates' => new stdClass, 'calls' => [['path' => '', 'method' => 'pay', 'params' => [], 'metadata' => []]]]],
        ]);

    $response->assertOk();
    expect($response->getContent())->toContain('alpha \/ alpha \/ 111')->not->toContain('beta');
});

/** Signs in as a real request would: unlike actingAs(), this leaves the default guard alone. */
function sessionSignIn(BotUser $user): void
{
    Auth::guard('tbe-web')->login(test()->panelAccount($user));
}

function realUpdateRequest(object $test, string $url, string $stateKey, string $method): TestResponse
{
    $html = $test->get($url)->assertOk()->getContent();
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $m);
    $snapshot = collect($m[1])->map(fn ($s) => html_entity_decode($s))->first(fn ($s) => str_contains($s, $stateKey));

    tenancy()->end();
    app()->forgetScopedInstances();

    return $test->withHeader('X-Livewire', '1')
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson(app('livewire')->getUpdateUri(), [
            '_token' => csrf_token(),
            'components' => [['snapshot' => $snapshot, 'updates' => new stdClass, 'calls' => [['path' => '', 'method' => $method, 'params' => [], 'metadata' => []]]]],
        ]);
}

it('keeps a resource screen working on Livewire requests for the session\'s user', function () {
    [, $user] = webBot();
    Gate::before(fn ($u, $ability) => $u instanceof BotUser && $ability === 'web.manage' ? true : null);
    sessionSignIn($user);

    realUpdateRequest($this, '/manage/alpha/bot-users', 'ListBotUsers', '$refresh')->assertOk();
});

it('refuses Livewire actions once the session has ended', function () {
    grantManage();
    [, $user] = webBot();
    sessionSignIn($user);

    $html = $this->get('/manage/alpha/buy')->assertOk()->getContent();
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $m);
    $snapshot = collect($m[1])->map(fn ($s) => html_entity_decode($s))->first(fn ($s) => str_contains($s, 'paidContext'));

    Auth::guard('tbe-web')->logout();
    tenancy()->end();
    app()->forgetScopedInstances();

    $response = $this->withHeader('X-Livewire', '1')
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson(app('livewire')->getUpdateUri(), [
            '_token' => csrf_token(),
            'components' => [['snapshot' => $snapshot, 'updates' => new stdClass, 'calls' => [['path' => '', 'method' => 'pay', 'params' => [], 'metadata' => []]]]],
        ]);

    expect($response->status())->toBe(401)
        ->and($response->getContent())->not->toContain('alpha \/ alpha');
});

it('sends nothing about the user to an outside avatar service', function () {
    grantManage();
    [, $user] = webBot();
    sessionSignIn($user);

    $this->get('/manage/alpha/buy')->assertOk()->assertDontSee('ui-avatars.com')->assertSee('data:image/svg+xml', false);
});

it('takes plugins a package registers from register, even before essence', function () {
    grantManage();
    [, $user] = webBot();
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/manage/alpha/late')->assertOk()->assertSee('Late plugin');
});

it('refuses a plugin registered after the panels were built, instead of a silent 404', function () {
    webPanels()->manage(LatePlugin::class);
})->throws(LogicException::class, 'register()');

it('stops a page when a handler gate refuses, on every request', function () {
    grantManage();
    [, $user] = webBot();
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    handlerGates()->add(RefusingGate::class);

    $this->get('/manage/alpha/buy')->assertForbidden();
});

it('asks for the page ability, not only the panel\'s', function () {
    [, $user] = webBot();
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->get('/manage/alpha/ticket')->assertOk();

    expect(LockedPage::canAccess())->toBeFalse();
});

it('lets no other site frame the pages', function () {
    grantManage();
    [, $user] = webBot();
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/manage/alpha/buy')->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
    $this->get('/alpha/web/open')->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
});

it('sends a one-time sign-in link for /panel, only to those who may manage', function () {
    [$bot, $user] = webBot();

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/panel', 111))->assertOk();
    Http::assertNotSent(fn ($request) => str_contains($request->body(), '/web/login/'));

    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/panel', 111))->assertOk();
    Http::assertSent(fn ($request) => str_contains(stripslashes(urldecode($request->body())), '/web/login/'));
});

it('sends only a browser link for /panel, never a Mini App button', function () {
    [$bot] = webBot();
    grantManage();

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/panel', 111))->assertOk();

    Http::assertSent(fn ($request) => str_contains(stripslashes(urldecode($request->body())), '/web/login/'));
    Http::assertNotSent(fn ($request) => str_contains(stripslashes(urldecode($request->body())), 'web_app'));
});

it('no longer signs in from Telegram initData', function () {
    webBot();

    $this->post('/alpha/web/auth/telegram')->assertNotFound();
    $this->get('/alpha/web/open')->assertOk()->assertDontSee('telegram-web-app.js');
});

it('creates an account on the first link sign-in and reuses it after', function () {
    grantManage();
    [, $user] = webBot();
    $this->withoutMiddleware(ValidateCsrfToken::class);

    foreach ([1, 2] as $round) {
        $path = parse_url(app(LoginLinks::class)->create($user), PHP_URL_PATH);
        $this->post($path)->assertRedirect(url('manage/alpha'));
        Auth::guard('tbe-web')->logout();
    }

    expect(TestAccount::query()->count())->toBe(1)
        ->and(TestAccount::query()->first()->linkedPeerIds())->toBe([111]);
});

it('warns before a sign-in creates a new account, not for a linked one', function () {
    [, $user] = webBot();
    $path = fn () => parse_url(app(LoginLinks::class)->create($user), PHP_URL_PATH);

    $this->get($path())->assertOk()->assertSee(__('tbe-essence::web.login_new_account'));

    $this->panelAccount($user);
    $this->get($path())->assertOk()->assertDontSee(__('tbe-essence::web.login_new_account'));
});

it('lets one account manage every bot its Telegram accounts may manage', function () {
    grantManage();
    [, $alpha] = webBot();
    [, $beta] = webBot('beta', 222);
    webBot('gamma', 333);

    $this->actingAs($this->panelAccount($alpha, $beta), 'tbe-web');

    $this->get('/manage/alpha/buy')->assertOk();
    $this->get('/manage/beta/buy')->assertOk();
    $this->get('/manage/gamma/buy')->assertNotFound();
});

it('acts as the bot owner when the account links the owner and another user', function () {
    grantManage();
    [$bot, $member] = webBot();
    $owner = BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => $bot->bot_owner_peer_id]);

    $account = $this->panelAccount($member, $owner);

    expect($account->actingBotUser($bot)?->id)->toBe($owner->id);
    $this->actingAs($account, 'tbe-web')->get('/manage/alpha/buy')->assertOk()
        ->assertSee(__('tbe-essence::web.acting_as', ['name' => '@'.$owner->telegramUser->username]));
});

it('skips a linked Telegram account that may not manage, for the next one', function () {
    [$bot, $member] = webBot();
    $staff = BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => TelegramUser::factory()->create()->peer_id]);
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' && $u->is($staff) ? true : null);

    expect($this->panelAccount($member, $staff)->actingBotUser($bot)?->id)->toBe($staff->id);
});

it('lists no bot where a linked Telegram account has no bot user, even its own', function () {
    grantManage();
    [$bot] = webBot();
    $owner = TelegramUser::query()->where('peer_id', $bot->bot_owner_peer_id)->firstOrFail();
    $account = TestAccount::query()->create(['name' => 'Owner']);
    $account->linkTelegram($owner->peer_id);

    expect($account->panelBotUsers())->toBe([]);
    $this->actingAs($account, 'tbe-web')->get('/manage/alpha/buy')->assertForbidden();
});

it('links a Telegram account to one web account only', function () {
    [, $user] = webBot();
    $this->panelAccount($user);

    $this->panelAccount($user);
})->throws(TelegramAccountTaken::class);

it('says what is wrong with an account model that cannot sign in', function () {
    config()->set('tbe-essence.web.account_model', BotUser::class);

    WebAccounts::model();
})->throws(LogicException::class, 'TelegramAccount');

it('asks about each bot inside its own tenancy, as tenant-scoped rules need', function () {
    [$alpha, $inAlpha] = webBot();
    [$beta, $inBeta] = webBot('beta', 222);
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' && tenancy()->tenant?->getKey() === $u->bot_id ? true : null);

    tenancy()->initialize($alpha);
    $account = $this->panelAccount($inAlpha, $inBeta);

    expect(array_keys($account->panelBotUsers()))->toBe([$alpha->id, $beta->id])
        ->and(tenancy()->tenant?->getKey())->toBe($alpha->id);
});

it('frees the Telegram accounts of a deleted account for a new sign-in', function () {
    grantManage();
    [, $user] = webBot();
    $this->panelAccount($user)->delete();
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->post(parse_url(app(LoginLinks::class)->create($user), PHP_URL_PATH))->assertRedirect(url('manage/alpha'));

    expect(TestAccount::query()->count())->toBe(1);
});

it('frees a link whose account was removed without Eloquent', function () {
    [, $user] = webBot();
    TestAccount::query()->whereKey($this->panelAccount($user)->getKey())->toBase()->delete();

    expect($this->panelAccount($user)->linkedPeerIds())->toBe([111]);
});

it('shows the panel in English by default, whatever the bot speaks', function () {
    grantManage();
    [, $user] = webBot();
    app()->setLocale('fa');
    config()->set('app.locale', 'fa');
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/manage/alpha/buy')->assertOk()->assertSee('lang="en"', false)->assertSee('فارسی');
    $this->get('/alpha/web/open')->assertOk()->assertSee(__('tbe-essence::web.open_from_bot', [], 'en'));
});

it('switches the panel to a language the viewer picks, and only to listed ones', function () {
    grantManage();
    [, $user] = webBot();
    $this->actingAs($this->panelAccount($user), 'tbe-web');

    $this->get('/tbe-web/locale/fa')->assertRedirect();
    $this->get('/manage/alpha/buy')->assertOk()->assertSee('lang="fa"', false)->assertSee('dir="rtl"', false)->assertSee('English');

    $this->get('/tbe-web/locale/xx')->assertNotFound();
});

it('runs work in the bot\'s language from the panel\'s', function () {
    [$bot] = webBot();
    app()->setLocale('en');
    $this->app->bind(ResolvesBotLocale::class, fn () => new class implements ResolvesBotLocale
    {
        public function resolve(Bot $bot): string
        {
            return 'fa';
        }
    });

    expect(inBotLocale(fn () => app()->getLocale(), $bot))->toBe('fa')
        ->and(app()->getLocale())->toBe('en');
});

it('charts new members on the dashboard, from this bot only', function () {
    grantManage();
    [$bot, $member] = webBot();
    webBot('beta', 222);

    $account = $this->panelAccount($member);
    $this->actingAs($account, 'tbe-web')->get('/manage/alpha')->assertOk();

    $this->enterPanel('manage', $bot, $account);
    $chart = Livewire::test(NewMembersChart::class)
        ->assertSee(__('tbe-essence::web.charts.new_members'));

    $data = (fn () => $this->getCachedData())->call($chart->instance());
    expect(array_sum($data['datasets'][0]['data']))->toBe(1.0);
});
