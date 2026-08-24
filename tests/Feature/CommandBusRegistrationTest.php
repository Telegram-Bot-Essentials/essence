<?php

declare(strict_types=1);

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\Commands\CommandBus;

/*
 * Characterisation tests for CommandBus registration.
 *
 * Registration order decides who wins a name collision, and today that
 * order comes from TelegramWebhookController::initializeOptions(), which
 * scans the consuming app FIRST and essence's own built-ins LAST. Since
 * addCommand() replaces on a duplicate name, essence currently overrides
 * the app — the opposite of what a consumer would expect.
 *
 * Phase 0 moves registration into the provider and inverts this
 * deliberately, so pin the current rule first.
 */

class AppGreetCommand extends Command
{
    protected string $name = 'greet';

    protected int $perm = 0;

    protected string $description = 'from the app';

    public function handle(): ?bool
    {
        return true;
    }
}

class PackageGreetCommand extends Command
{
    protected string $name = 'greet';

    protected int $perm = 0;

    protected string $description = 'from the package';

    public function handle(): ?bool
    {
        return true;
    }
}

class AliasedCommand extends Command
{
    protected string $name = 'aliased';

    protected array $aliases = ['a1', 'a2'];

    protected int $perm = 0;

    public function handle(): ?bool
    {
        return true;
    }
}

class RivalAliasCommand extends Command
{
    protected string $name = 'rival';

    protected array $aliases = ['a1'];

    protected int $perm = 0;

    public function handle(): ?bool
    {
        return true;
    }
}

it('registers a command under its name', function () {
    $bus = new CommandBus;
    $bus->addCommand(AppGreetCommand::class);

    expect(array_keys($bus->getCommands()))->toBe(['greet']);
});

it('lets the last registration of a name win', function () {
    // This is the whole precedence mechanism. Because initializeOptions()
    // scans essence last, the package currently beats the consuming app.
    $bus = new CommandBus;
    $bus->addCommand(AppGreetCommand::class);
    $bus->addCommand(PackageGreetCommand::class);

    expect($bus->getCommands()['greet']->getDescription())->toBe('from the package');
});

it('is idempotent when the same command is registered twice', function () {
    // Required today because the per-request scan re-registers everything.
    $bus = new CommandBus;
    $bus->addCommand(AliasedCommand::class);
    $bus->addCommand(AliasedCommand::class);

    expect(array_keys($bus->getCommands()))->toBe(['aliased']);
});

it('rejects an alias already claimed by a different command', function () {
    $bus = new CommandBus;
    $bus->addCommand(AliasedCommand::class);

    expect(fn () => $bus->addCommand(RivalAliasCommand::class))
        ->toThrow(LogicException::class);
});

it('rejects an alias that collides with an existing command name', function () {
    $bus = new CommandBus;
    $bus->addCommand(AppGreetCommand::class);

    $clashing = new class extends Command
    {
        protected string $name = 'other';

        protected array $aliases = ['greet'];

        protected int $perm = 0;

        public function handle(): ?bool
        {
            return true;
        }
    };

    expect(fn () => $bus->addCommand($clashing))->toThrow(LogicException::class);
});
