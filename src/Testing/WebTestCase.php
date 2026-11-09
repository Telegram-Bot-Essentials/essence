<?php

namespace TelegramBotEssentials\Essence\Testing;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Testing\TestResponse;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Livewire\LivewireManager;
use Livewire\LivewireServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\Filament\PanelActor;
use TelegramBotEssentials\Essence\Web\Filament\PanelBot;
use TelegramBotEssentials\Essence\Web\WebAccounts;

/** TestCase with the web panels on and Filament booted, for essence and its companions. */
abstract class WebTestCase extends TestCase
{
    /** Read while providers register, which Testbench does before defineEnvironment(). */
    protected function setUp(): void
    {
        $_ENV['TBE_WEB_ENABLED'] = $_SERVER['TBE_WEB_ENABLED'] = 'true';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['TBE_WEB_ENABLED'], $_SERVER['TBE_WEB_ENABLED']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            LivewireServiceProvider::class,
            PowerJoinsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            ...parent::getPackageProviders($app),
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('tbe-essence.web.account_model', TestAccount::class);
        $app['config']->set('auth.providers.tbe-web-accounts.model', TestAccount::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    /** A web account linked to the Telegram accounts of $users, in this order. */
    public function panelAccount(BotUser ...$users): TestAccount
    {
        $account = TestAccount::query()->create(['name' => 'Account']);
        foreach ($users as $user) {
            $account->linkTelegram($user->peerId());
        }

        return $account;
    }

    /**
     * Signs $user in on $panel through a linked account, with what the panel's
     * middleware would have set, for Livewire::test(). Returns the BotUser the
     * panel acts as, which is $user unless the account links its bot's owner.
     */
    public function enterPanel(string $panel, Bot $bot, BotUser|TestAccount $user): BotUser
    {
        $account = $user instanceof TestAccount ? $user : $this->panelAccount($user);
        $this->actingAs($account, WebAccounts::guard());

        Filament::setCurrentPanel($panel);
        Filament::setTenant(PanelBot::query()->whereKey($bot->getKey())->firstOrFail(), true);
        Filament::bootCurrentPanel();
        tenancy()->initialize($bot);

        $actor = PanelActor::current();
        if (! $actor instanceof BotUser) {
            throw new \LogicException('That account may not manage this bot: grant web.manage first.');
        }

        $hook = wHook();
        $hook->setBot($bot);
        $token = $bot->getAttribute('bot_token');
        $hook->setApi(telegramApi(is_string($token) ? $token : ''));
        $hook->setUpdate(new Update([]));
        $hook->setUser($actor);

        return $actor;
    }

    /**
     * Calls $method on the Livewire component of the page at $url through
     * the real route and middleware, as a browser does; Livewire::test()
     * skips them. $stateKey picks the component: a word in its snapshot,
     * such as its class name. $between runs after the page loads, before
     * the update: sign out there, or take an ability away.
     *
     * @param  list<mixed>  $params
     * @return TestResponse<Response>
     */
    protected function livewireRequest(string $url, string $stateKey, string $method, array $params = [], ?\Closure $between = null): TestResponse
    {
        $html = (string) $this->get($url)->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = collect($matches[1])
            ->map(fn (string $s): string => html_entity_decode($s))
            ->first(fn (string $s): bool => str_contains($s, $stateKey));

        if ($snapshot === null) {
            throw new \LogicException("No Livewire component on $url mentions $stateKey.");
        }

        // A browser's next request starts fresh.
        tenancy()->end();
        app()->forgetScopedInstances();
        if ($between !== null) {
            $between();
        }

        $updateUri = app(LivewireManager::class)->getUpdateUri();
        if (! is_string($updateUri)) {
            throw new \LogicException('Livewire has no update route.');
        }

        return $this->withHeader('X-Livewire', '1')
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson($updateUri, [
                '_token' => csrf_token(),
                'components' => [['snapshot' => $snapshot, 'updates' => new \stdClass, 'calls' => [['path' => '', 'method' => $method, 'params' => $params, 'metadata' => []]]]],
            ]);
    }
}
