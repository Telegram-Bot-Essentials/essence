<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQueryInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

trait CanResolveCallbackQuery
{
    use CanBuildDependencyInjectedClass;
    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveCallbackQuery(CallbackQueryInterface|string $callbackQuery): CallbackQueryInterface
    {
        if (!is_a($callbackQuery, CallbackQueryInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'CallbackQuery class "%s" should be an instance of "%s"',
                    is_object($callbackQuery) ? $callbackQuery::class : $callbackQuery,
                    CallbackQueryInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($callbackQuery);
    }
}
