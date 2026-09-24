<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\TextMatchers\TextMatcherInterface;

trait CanResolveTextMatcher
{
    use CanBuildDependencyInjectedClass;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveTextMatcher(TextMatcherInterface|string $textMatcher): TextMatcherInterface
    {
        if (! is_a($textMatcher, TextMatcherInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'TextMatcher class "%s" should be an instance of "%s"',
                    (string) $textMatcher,
                    TextMatcherInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($textMatcher);
    }
}
