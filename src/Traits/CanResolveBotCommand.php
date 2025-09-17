<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait CanResolveBotCommand
{
    use CanBuildDependencyInjectedClass;

    /**
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
