<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryInterface;

trait CanResolveCallbackQuery
{
    use CanBuildDependencyInjectedClass;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveCallbackQuery(CallbackQueryInterface|string $callbackQuery): CallbackQueryInterface
    {
        if (! is_a($callbackQuery, CallbackQueryInterface::class, true)) {
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
