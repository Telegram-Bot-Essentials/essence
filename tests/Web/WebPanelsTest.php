<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\LatePlugin;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\LockedPage;
use TelegramBotEssentials\Essence\Tests\Web\Fixtures\RefusingGate;
use TelegramBotEssentials\Essence\Web\Filament\PanelUser;
use TelegramBotEssentials\Essence\Web\LoginLinks;
use TelegramBotEssentials\Essence\Web\TelegramInitData;

uses(RefreshDatabase::class);

function signedInitData(string $token, int $peerId, ?int $authDate = null): string
{
    $fields = ['auth_date' => (string) ($authDate ?? time()), 'query_id' => 'AAA', 'user' => json_encode(['id' => $peerId, 'first_name' => 'Sara'])];
    ksort($fields);
    $check = collect($fields)->map(fn ($v, $k) => "$k=$v")->implode("\n");
    $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', $token, 'WebAppData', true));

    return http_build_query($fields);
}

function webBot(string $uniqueId = 'alpha', int $peer = 111): array
{
    $owner = TelegramUser::factory()->create();
    $bot = Bot::factory()->create(['unique_id' => $uniqueId, 'bot_token' => "1:$uniqueId", 'bot_owner_peer_id' => $owner->peer_id, 'activated_until' => now()->addYear()]);
    TelegramUser::query()->firstOrCreate(['peer_id' => $peer], TelegramUser::factory()->raw(['peer_id' => $peer]));
    $user = BotUser::query()->create(['bot_id' => $bot->id, 'telegram_user_peer_id' => $peer]);

    return [$bot, $user];
}

it('accepts valid initData and rejects tampered or old data', function () {
    $good = signedInitData('1:t', 5);

    expect(TelegramInitData::verify($good, '1:t', 60)['user']['id'])->toBe(5)
        ->and(TelegramInitData::verify($good, '1:other', 60))->toBeNull()
        ->and(TelegramInitData::verify(str_replace('Sara', 'Eve', $good), '1:t', 60))->toBeNull()
        ->and(TelegramInitData::verify(signedInitData('1:t', 5, time() - 120), '1:t', 60))->toBeNull()
        ->and(TelegramInitData::verify('', '1:t', 60))->toBeNull();
});

it('signs a user in from initData, and refuses another bot\'s signature', function () {
    [$bot, $user] = webBot();

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/alpha/web/auth/telegram', ['init_data' => signedInitData('1:wrong', 111)])->assertForbidden();
    expect(Auth::guard('tbe-web')->check())->toBeFalse();

    $this->postJson('/alpha/web/auth/telegram', ['init_data' => signedInitData('1:alpha', 111), 'panel' => 'customer'])
        ->assertOk()->assertJsonPath('redirect', url('app/alpha'));
    expect(Auth::guard('tbe-web')->id())->toBe($user->id);
});

it('sends signed-out visitors to the bot sign-in page', function () {
    webBot();

    $this->get('/app/alpha/buy')->assertRedirect('/alpha/web/open?panel=customer');
});

it('keeps a session on its own bot', function () {
    [, $user] = webBot();
    webBot('beta', 222);

    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $this->get('/app/alpha/buy')->assertOk()->assertSee('Buy service');
    $this->get('/app/beta/buy')->assertNotFound();
});

it('keeps the manage panel behind an ability', function () {
    [, $user] = webBot();
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $this->get('/manage/alpha/ticket')->assertForbidden();

    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->get('/manage/alpha/ticket')->assertOk()->assertSee('Ticket #42');
});

it('spends a login link on POST only, once', function () {
    [, $user] = webBot();
    $url = app(LoginLinks::class)->create($user, 'customer');
    $path = parse_url($url, PHP_URL_PATH);

    $this->get($path)->assertOk();
    $this->get($path)->assertOk();

    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->post($path)->assertRedirect(url('app/alpha'));
    expect(Auth::guard('tbe-web')->id())->toBe($user->id);

    Auth::guard('tbe-web')->logout();
    $this->post($path)->assertStatus(410);
    $this->get($path)->assertStatus(410);
});

it('lists only the current bot\'s users in a stock Filament table', function () {
    [, $user] = webBot();
    webBot('beta', 222);
    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $this->get('/manage/alpha/bot-users')->assertOk()->assertSee('111')->assertDontSee('222');
});

it('has tenant, bot, api and user on a real Livewire update request', function () {
    [, $user] = webBot();
    webBot('beta', 222);
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $html = $this->get('/app/alpha/buy')->assertOk()->getContent();
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
    Auth::guard('tbe-web')->login(PanelUser::find($user->id));
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
    [, $user] = webBot();
    sessionSignIn($user);

    $html = $this->get('/app/alpha/buy')->assertOk()->getContent();
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
    [, $user] = webBot();
    sessionSignIn($user);

    $this->get('/app/alpha/buy')->assertOk()->assertDontSee('ui-avatars.com')->assertSee('data:image/svg+xml', false);
});

it('takes plugins a package registers from register, even before essence', function () {
    [, $user] = webBot();
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $this->get('/app/alpha/late')->assertOk()->assertSee('Late plugin');
});

it('refuses a plugin registered after the panels were built, instead of a silent 404', function () {
    webPanels()->customer(LatePlugin::class);
})->throws(LogicException::class, 'register()');

it('stops a page when a handler gate refuses, on every request', function () {
    [, $user] = webBot();
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    handlerGates()->add(RefusingGate::class);

    $this->get('/app/alpha/buy')->assertForbidden();
});

it('asks for the page ability, not only the panel\'s', function () {
    [, $user] = webBot();
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->get('/manage/alpha/ticket')->assertOk();

    expect(LockedPage::canAccess())->toBeFalse();
});

it('lets only Telegram Web frame the pages', function () {
    [, $user] = webBot();
    $this->actingAs(PanelUser::find($user->id), 'tbe-web');

    $this->get('/app/alpha/buy')->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://web.telegram.org");
    $this->get('/alpha/web/open')->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://web.telegram.org");
});

it('sends a one-time sign-in link for /panel, only to those who may manage', function () {
    [$bot, $user] = webBot();

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/panel', 111))->assertOk();
    Http::assertNotSent(fn ($request) => str_contains($request->body(), '/web/login/'));

    Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/panel', 111))->assertOk();
    Http::assertSent(fn ($request) => str_contains(stripslashes(urldecode($request->body())), '/web/login/'));
});
