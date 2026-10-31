<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\BotCommandMenu;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

/*
 * Essence holds no access model. A handler names a Gate ability (or none,
 * for everyone) and the buses ask Laravel's Gate with the BotUser; who
 * holds what is the app's business. With nothing defined, the Gate denies
 * every ability to everyone, the bot owner included.
 */

uses(RefreshDatabase::class);

class AccessNamedCommand extends Command
{
    public static int $runs = 0;

    protected string $name = 'access_named';

    protected ?string $ability = 'reports.view';

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

it('opens a handler without an ability to everyone', function () {
    $handler = new AccessPublicCommand;

    expect(handlerAbility($handler))->toBeNull()
        ->and(isPublicHandler($handler))->toBeTrue()
        ->and(canUseHandler($handler, $this->member))->toBeTrue();
});

it('denies an undefined ability to everyone, the owner included', function () {
    $handler = new AccessNamedCommand;

    expect(handlerAbility($handler))->toBe('reports.view')
        ->and(canUseHandler($handler, $this->member))->toBeFalse()
        ->and(canUseHandler($handler, $this->owner))->toBeFalse();

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: (int) $this->bot->bot_owner_peer_id))->assertOk();
    expect(AccessNamedCommand::$runs)->toBe(0);
});

it('asks the app\'s Gate with the bot user', function () {
    Gate::define('reports.view', fn (BotUser $user) => $user->is($this->owner));

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: 501))->assertOk();
    expect(AccessNamedCommand::$runs)->toBe(0);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: (int) $this->bot->bot_owner_peer_id))->assertOk();
    expect(AccessNamedCommand::$runs)->toBe(1);
});

it('checks hasAccess() against the webhook user', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 501))->assertOk();

    expect(hasAccess(null))->toBeTrue()
        ->and(hasAccess('reports.view'))->toBeFalse();
});

it('lets a test grant an ability to a plain user', function () {
    $this->grant($this->member, 'reports.view');

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/access_named', peerId: 501))->assertOk();

    expect(AccessNamedCommand::$runs)->toBe(1)
        ->and(Gate::forUser($this->member)->allows('reports.export'))->toBeFalse()
        ->and(Gate::forUser($this->owner)->allows('reports.view'))->toBeFalse();
});

it('leaves commands that need an ability out of the published menu', function () {
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
