<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

trait CanResolveStateAnswer
{
    use CanBuildDependencyInjectedClass;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveStateAnswer(StateAnswerInterface|string $stateAnswer): StateAnswerInterface
    {
        if (!is_a($stateAnswer, StateAnswerInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'StateAnswer class "%s" should be an instance of "%s"',
                    is_object($stateAnswer) ? $stateAnswer::class : $stateAnswer,
                    StateAnswerInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($stateAnswer);
    }
}
