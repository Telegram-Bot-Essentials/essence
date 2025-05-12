<?php

declare(strict_types=1);

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Traits\CanResolveStateAnswer;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

/**
 * Class CommandBus.
 */
class StateAnswerBus
{
    use CanResolveStateAnswer;

    private array $stateAnswerTypes = [];

    public function getStateAnswerTypes(): array
    {
        return $this->stateAnswerTypes;
    }

    /**
     * @param iterable $stateAnswers
     * @return StateAnswerBus
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addStateAnswers(iterable $stateAnswers): self
    {
        foreach ($stateAnswers as $stateAnswer) {
            $this->addStateAnswer($stateAnswer);
        }

        return $this;
    }

    /**
     * @param StateAnswerInterface|string $stateAnswer
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addStateAnswer(StateAnswerInterface|string $stateAnswer): void
    {
        $stateAnswer = $this->resolveStateAnswer($stateAnswer);

        $this->stateAnswerTypes[$stateAnswer->getType()] = $stateAnswer;
    }

    public function removeStateAnswers(array $names): self
    {
        foreach ($names as $name) {
            $this->removeStateAnswer($name);
        }

        return $this;
    }

    public function removeStateAnswer(string $name): self
    {
        unset($this->stateAnswerTypes[$name]);

        return $this;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function handleStateAnswer(array $decodedStates): bool
    {
        if (!wHook()->update()->isType('message')) return false;

        $type = $decodedStates['type'];
        $method = $decodedStates['method'];
        $params = $decodedStates['params'];

        $key = $this->stateAnswerTypes[$type] ?? null;
        if (empty($key)) {
            Log::error('answer "' . $type . '" is not registered');
            try {
                wHook()->user()->changeState();
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
            return false;
        }

        $resolvedStateAnswer = $this->resolveStateAnswer($key);
        if (!$this->hasValidField($resolvedStateAnswer->getAllowedFields())) return false;
        $this->handler($resolvedStateAnswer, $method, $params);
        return true;
    }

    private function hasValidField(array $fields): bool
    {
        $message = wHook()->update()->getMessage();
        foreach ($fields as $field) {
            if ($message->has($field)) return true;
        }
        return false;
    }

    protected function handler(StateAnswerInterface $resolvedStateAnswer, string $method, array $params): void
    {
        if (wHook()->user()->power < $resolvedStateAnswer->getPerm()) return;
        $resolvedStateAnswer->handle($method, $params);
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function cancelHandler(?string $state): bool
    {
        if (!$state) return false;
        $decodedState = decodeAnswerState($state);
        $decodedState['method'] = 'cancel';
        return $this->handleStateAnswer($decodedState);
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function processStateAnswers(): bool
    {
        $answerState = decodeAnswerState(wHook()->user()->state);
        return $this->handleStateAnswer($answerState);
    }
}
