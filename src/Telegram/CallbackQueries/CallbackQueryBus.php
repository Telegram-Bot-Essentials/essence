<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Traits\CanResolveCallbackQuery;

/**
 * Class CommandBus.
 */
class CallbackQueryBus
{
    use CanResolveCallbackQuery;

    private array $callbackQueryTypes = [];

    public function getCallbackQueryTypes(): array
    {
        return $this->callbackQueryTypes;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addCallbackQueries(iterable $replyKeys): self
    {
        foreach ($replyKeys as $replyKey) {
            $this->addCallbackQuery($replyKey);
        }

        return $this;
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function addCallbackQuery(CallbackQueryInterface|string $callbackQuery): void
    {
        $callbackQuery = $this->resolveCallbackQuery($callbackQuery);

        $this->callbackQueryTypes[$callbackQuery->getType()] = $callbackQuery;
    }

    public function removeCallbackQueries(array $names): self
    {
        foreach ($names as $name) {
            $this->removeCallbackQuery($name);
        }

        return $this;
    }

    public function removeCallbackQuery(string $name): self
    {
        unset($this->callbackQueryTypes[$name]);

        return $this;
    }

    /**
     * @param string $callbackQuery
     * @return bool
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function routeQuery(string $callbackQuery): bool
    {
        $callbackQueryData = decodeCallback($callbackQuery);

        return $this->route($callbackQueryData);
    }

    /**
     * @param array $callbackQueryData
     * @return bool
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    private function route(array $callbackQueryData): bool
    {
        $method = $callbackQueryData['method'];
        $type = $callbackQueryData['type'];

        $key = $this->callbackQueryTypes[$type] ?? null;
        if (empty($key)) {
            Log::error('query "' . $type . '" is not registered');
            throw new TbeLogicException(__('tbe::general.callbackQuery.willBeAddedInTheFuture'));
        }

        $resolvedCallbackQuery = $this->resolveCallbackQuery($key);
        return $this->handler($resolvedCallbackQuery, $method, $callbackQueryData['params']);
    }

    protected function handler(CallbackQueryInterface $resolvedCallbackQuery, string $method, array $params): bool
    {
        if (!$resolvedCallbackQuery->isEnabled()) return false;
        if (!hasAccess($resolvedCallbackQuery->getPerm())) return false;
        $resolvedCallbackQuery->setParams($params);
        $resolvedCallbackQuery->setMethod($method);
        return $resolvedCallbackQuery->handle() ?? true;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function processCallbackQueries(): bool
    {
        $update = wHook()->update();

        if (!$update->isType('callback_query')) return false;
        $callbackQueryData = decodeCallback($update->callbackQuery->data);

        return $this->route($callbackQueryData);
    }
}
