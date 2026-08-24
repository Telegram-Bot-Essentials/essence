<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQueryInterface;

trait CanResolveInlineQuery
{
    use CanBuildDependencyInjectedClass;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveInlineQuery(InlineQueryInterface|string $inlineQuery): InlineQueryInterface
    {
        if (! is_a($inlineQuery, InlineQueryInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'InlineQuery class "%s" should be an instance of "%s"',
                    is_object($inlineQuery) ? $inlineQuery::class : $inlineQuery,
                    InlineQueryInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($inlineQuery);
    }
}
