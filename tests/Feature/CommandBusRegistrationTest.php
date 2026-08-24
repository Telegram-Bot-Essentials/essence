<?php

declare(strict_types=1);

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\Commands\CommandBus;

/*
 * Characterisation tests for CommandBus registration.
 *
 * Registration order decides who wins a name collision. essence registers
 * its built-ins in boot(), companion packages register in their own
 * boot(), and the consuming app is scanned in a booted() callback after
 * every provider has booted. addCommand() replaces on a duplicate name, so
 * the app wins — the inverse of the old per-request scan, which ran
 * essence last and let the package override the app.
 */

class AppGreetCommand extends Command
{
    protected string $name = 'greet';

    protected int $perm = 0;

    protected string $descriptionKey = 'from the app';

    public function handle(): ?bool
    {
        return true;
    }
}

class PackageGreetCommand extends Command
{
    protected string $name = 'greet';

    protected int $perm = 0;

    protected string $descriptionKey = 'from the package';

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
    // Companion packages register in their own boot(), which can run more
    // than once across a worker's life under some reload paths.
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
