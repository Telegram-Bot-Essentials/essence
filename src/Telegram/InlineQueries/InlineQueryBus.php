<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanResolveInlineQuery;

class InlineQueryBus
{
    use CanResolveInlineQuery;

    private ?InlineQueryInterface $handler = null;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function setHandler(InlineQueryInterface|string $inlineQuery): void
    {
        $this->handler = $this->resolveInlineQuery($inlineQuery);
    }

    public function getHandler(): ?InlineQueryInterface
    {
        return $this->handler;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function processInlineQuery(): bool
    {
        $update = wHook()->update();

        if (!$update->isType('inline_query')) return false;
        if (!$this->handler) return false;

        $handler = $this->resolveInlineQuery($this->handler);

        if (!$handler->isEnabled()) return false;
        if (!hasAccess($handler->getPerm())) return false;

        $handler->handle();

        return true;
    }
}
