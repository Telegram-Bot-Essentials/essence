<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Contracts\AuthorizesAccess;
use TelegramBotEssentials\Essence\Exceptions\TbeException;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\BotCommandMenu;
use TelegramBotEssentials\Essence\Support\DefaultAuthorizer;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\AdminPanelKey;

/*
 * Access goes through the AuthorizesAccess contract. Essence's default
 * leaves handlers without a permission open and lets only the bot owner and
 * developer use the rest; a role package swaps the whole model by
 * rebinding the contract.
 */

uses(RefreshDatabase::class);

class AccessNamedCommand extends Command
{
    public static int $runs = 0;

    protected string $name = 'access_named';

    protected ?string $permission = 'reports.view';

    public function handle(): ?bool
    {
        self::$runs++;

        return true;
    }
}

class AccessPublicCommand extends Command
{
    protected string $name = 'access_public';

    public function handle(): ?bool
    {
        return true;
    }
}

beforeEach(function () {
    commandBus()->addCommands([AccessNamedCommand::class, AccessPublicCommand::class]);
    AccessNamedCommand::$runs = 0;

    $this->bot = $this->makeBot();
    $this->member = $this->makeBotUser($this->bot, 501);
    $this->owner = BotUser::factory()->create([
        'bot_id' => $this->bot->id,
        'telegram_user_peer_id' => $this->bot->bot_owner_peer_id,
    ]);
});

it('opens a handler without a permission to everyone', function () {
    $handler = new AccessPublicCommand;

    expect(handlerPermission($handler))->toBeNull()
        ->and(isPublicHandler($handler))->toBeTrue()
        ->and(canUseHandler($handler, $this->member))->toBeTrue();
});

it('keeps a named permission to the owner and developer by default', function () {
    $developer = $this->makeBotUser($this->bot, 504);
    // env() hands the developer id over as a string.
    config(['tbe-essence.developer.peer_id' => '504']);

    expect(authorizer()->allows($this->member, 'reports.view'))->toBeFalse()
        ->and(authorizer()->allows($this->owner, 'reports.view'))->toBeTrue()
        ->and(authorizer()->allows($developer, 'reports.view'))->toBeTrue()
        ->and(authorizer()->usersWith($this->bot, 'reports.view')->count())->toBe(0);
});

it('checks hasAccess() against the webhook user', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 501))->assertOk();

    expect(hasAccess(null))->toBeTrue()
        ->and(hasAccess('reports.view'))->toBeFalse();
});

it('denies a member a command that names a permission and runs it for the owner', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: 501))->assertOk();
    expect(AccessNamedCommand::$runs)->toBe(0);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: (int) $this->bot->bot_owner_peer_id))->assertOk();
    expect(AccessNamedCommand::$runs)->toBe(1);
});

it('lets a test grant a permission to a plain user', function () {
    $this->grant($this->member, 'reports.view');

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: 501))->assertOk();

    expect(AccessNamedCommand::$runs)->toBe(1)
        ->and(authorizer()->allows($this->member, 'reports.export'))->toBeFalse()
        ->and(authorizer()->usersWith($this->bot, 'reports.view')->pluck('id')->all())->toBe([$this->member->id]);
});

it('routes the buses through a rebound authorizer', function () {
    app()->bind(AuthorizesAccess::class, fn () => new class extends DefaultAuthorizer
    {
        public function allows(BotUser $user, ?string $permission): bool
        {
            return $permission === 'reports.view';
        }
    });

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: 501))->assertOk();

    expect(AccessNamedCommand::$runs)->toBe(1);
});

it('leaves commands that need a permission out of the published menu', function () {
    app(BotCommandMenu::class)->register($this->bot);

    $this->assertTelegramSent(function ($request) {
        if (! str_ends_with($request->url(), '/setMyCommands')) {
            return false;
        }

        $names = array_column(json_decode((string) $request['commands'], true), 'command');

        return in_array('help', $names, true)
            && in_array('access_public', $names, true)
            && ! in_array('access_named', $names, true);
    });
});

it('registers permissions once, grouped by package', function () {
    permissionRegistry()->register('reports.view', fn () => 'View reports', 'reports');
    permissionRegistry()->register('reports.export', 'Export reports', 'reports');
    permissionRegistry()->register('panels.manage', 'Manage panels');

    expect(permissionRegistry()->has('reports.view'))->toBeTrue()
        ->and(permissionRegistry()->get('reports.view')?->label())->toBe('View reports')
        ->and(array_keys(permissionRegistry()->grouped()))->toBe(['essence', 'reports', 'general'])
        ->and(array_keys(permissionRegistry()->grouped()['reports']))->toBe(['reports.view', 'reports.export']);

    permissionRegistry()->register('reports.view', 'again');
})->throws(TbeException::class, 'Permission "reports.view" is already registered');

it('gates the admin panel key by name', function () {
    $key = new AdminPanelKey;

    expect(handlerPermission($key))->toBe('essence.admin_panel')
        ->and(permissionRegistry()->has('essence.admin_panel'))->toBeTrue()
        ->and(canUseHandler($key, $this->member))->toBeFalse()
        ->and(canUseHandler($key, $this->owner))->toBeTrue();
});
