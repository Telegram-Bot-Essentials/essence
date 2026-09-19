<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanResolveStateAnswer;

/**
 * Class CommandBus.
 */
class StateAnswerBus
{
    use CanResolveStateAnswer;

    /** @var array<string, StateAnswerInterface> */
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
     * @param  array{type: ?string, method: ?string, params: array<mixed>}  $decodedStates
     *
     * @throws BindingResolutionException
     * @throws LogicException|TelegramSDKException
     */
    private function handleStateAnswer(array $decodedStates): bool
    {
        if (! wHook()->update()->isType('message')) {
            return false;
        }

        $type = (string) $decodedStates['type'];
        $method = (string) $decodedStates['method'];
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
        $resolvedStateAnswer->setParams($params);
        $resolvedStateAnswer->setMethod($method);
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
     * The reply keyboard the state answer behind $state wants shown, or null
     * when the state is unregistered or the answer keeps the default.
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function keyboardFor(string $state): ?Keyboard
    {
        $decoded = decodeAnswerState($state);
        $registered = $this->stateAnswerTypes[(string) $decoded['type']] ?? null;

        if ($registered === null) {
            return null;
        }

        $stateAnswer = $this->resolveStateAnswer($registered::class);

        if (! $stateAnswer->isEnabled() || ! hasAccess($stateAnswer->getPerm())) {
            return null;
        }

        $stateAnswer->setParams($decoded['params']);
        $stateAnswer->setMethod((string) $decoded['method']);

        return $stateAnswer->keyboard();
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
