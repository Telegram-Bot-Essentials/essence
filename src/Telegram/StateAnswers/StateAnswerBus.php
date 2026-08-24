<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanResolveStateAnswer;

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
     * @throws TelegramSDKException
     */
    public function cancelHandler(?string $state): bool
    {
        if (! $state) {
            return false;
        }
        $decodedState = decodeAnswerState($state);
        $decodedState['method'] = 'cancel';

        return $this->handleStateAnswer($decodedState);
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException|TelegramSDKException
     */
    private function handleStateAnswer(array $decodedStates): bool
    {
        if (! wHook()->update()->isType('message')) {
            return false;
        }

        $type = $decodedStates['type'];
        $method = $decodedStates['method'];
        $params = $decodedStates['params'];

        $key = $this->stateAnswerTypes[$type] ?? null;
        if (empty($key)) {
            tbeLog('essence')->warning('State answer "'.$type.'" is not registered');
            try {
                wHook()->user()->changeState();
            } catch (Exception $e) {
                tbeLog('essence')->error('Failed to reset user state: '.$e->getMessage(), ['exception' => $e]);
            }

            return false;
        }

        // Fresh instance to handle with: the registered one is a long-lived
        // template shared by every request on this worker.
        $resolvedStateAnswer = $this->resolveStateAnswer($key::class);
        if (! $this->hasValidField($resolvedStateAnswer->getAllowedFields())) {
            return false;
        }
        $this->handler($resolvedStateAnswer, $method, $params);

        return true;
    }

    private function hasValidField(array $fields): bool
    {
        $message = wHook()->update()->getMessage();
        foreach ($fields as $field) {
            if ($message->has($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws TelegramSDKException
     */
    protected function handler(StateAnswerInterface $resolvedStateAnswer, string $method, array $params): void
    {
        if (! $resolvedStateAnswer->isEnabled()) {
            return;
        }
        if (! hasAccess($resolvedStateAnswer->getPerm())) {
            return;
        }
        $resolvedStateAnswer->setParams($params);
        $resolvedStateAnswer->setMethod($method);
        $resolvedStateAnswer->handle();
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function processStateAnswers(bool $cancelOldProcess = false): bool
    {
        $answerState = decodeAnswerState(wHook()->user()->state);

        return $this->handleStateAnswer($answerState);
    }
}
