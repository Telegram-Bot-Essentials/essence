<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Traits\CanResolveCallbackQuery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

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
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function routeQuery(string $callbackQuery): void
    {
        $callbackQueryData = decodeCallback($callbackQuery);

        $this->route($callbackQueryData);
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function processCallbackQueries(): void
    {
        $update = wHook()->update();

        if (!$update->isType('callback_query')) return;
        $callbackQueryData = decodeCallback($update->callbackQuery->data);

        $this->route($callbackQueryData);
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    private function route(array $callbackQueryData): void
    {
        $type = $callbackQueryData['type'];

        $key = $this->callbackQueryTypes[$type] ?? null;
        if (empty($key)) {
            Log::error('query "' . $type . '" is not registered');
            throw new TbeLogicException(__('tbe::general.callbackQuery.willBeAddedInTheFuture'));
        }

        $resolvedCallbackQuery = $this->resolveCallbackQuery($key);
        $this->handler($resolvedCallbackQuery, $callbackQueryData['params']);
    }

    protected function handler(CallbackQueryInterface $resolvedCallbackQuery, array $params): void
    {
        if (!hasAccess($resolvedCallbackQuery->getPerm())) return;
        $resolvedCallbackQuery->setParams($params);
        $resolvedCallbackQuery->handle();
    }
}
