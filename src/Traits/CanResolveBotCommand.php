<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\Commands\CommandInterface;

trait CanResolveBotCommand
{
    use CanBuildDependencyInjectedClass;

    /**
     * Mirrors CanResolveCommand for console commands, whose Illuminate base
     * class already declares a protected resolveCommand().
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveBotCommand(CommandInterface|string $command): CommandInterface
    {
        if (! is_a($command, CommandInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'Command class "%s" should be an instance of "%s"',
                    is_object($command) ? $command::class : $command,
                    CommandInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($command);
    }
}
