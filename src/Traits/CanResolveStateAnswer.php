<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerInterface;

trait CanResolveStateAnswer
{
    use CanBuildDependencyInjectedClass;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveStateAnswer(StateAnswerInterface|string $stateAnswer): StateAnswerInterface
    {
        if (! is_a($stateAnswer, StateAnswerInterface::class, true)) {
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
