<?php

declare(strict_types=1);

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Traits\CanResolveCallbackQuery;
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
     */
    public function processCallbackQueries(): void
    {
        $update = wHook()->update();

        if (!$update->isType('callback_query')) return;
        $callbackQueryData = decodeCallback($update->callbackQuery->data);
        $type = $callbackQueryData['type'];

        $key = $this->callbackQueryTypes[$type] ?? null;
        if (empty($key)) {
            Log::error('query "' . $type . '" is not registered');
            try {
                wHook()->api()->answerCallbackQuery([
                    'callback_query_id' => $update->callbackQuery->id,
                    'text' => __('tbe::general.callbackQuery.willBeAddedInTheFuture'),
                    'show_alert' => true,
                    'cache_time' => 5,
                ]);
            } catch (TelegramSDKException $e) {
                Log::error($e->getMessage());
            }
            return;
        }

        $resolvedCallbackQuery = $this->resolveCallbackQuery($key);
        $this->handler($resolvedCallbackQuery, $callbackQueryData['params']);
    }

    protected function handler(CallbackQueryInterface $resolvedCallbackQuery, array $params): void
    {
        if (wHook()->user()->power < $resolvedCallbackQuery->getPerm()) return;
        $resolvedCallbackQuery->handle($params);
    }
}
