<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\TextMatchers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use TelegramBotEssentials\Essence\Traits\CanResolveTextMatcher;

/**
 * The last stop for a message: it only runs once no reply key and
 * no state answer took it, so a matcher can never steal a form answer or a
 * button press. The first registered matcher that matches wins, and one
 * the user has no access to is skipped rather than ending the search.
 */
class TextMatcherBus
{
    use CanCancelOldProcess;
    use CanResolveTextMatcher;

    /** @var array<class-string<TextMatcherInterface>, TextMatcherInterface> */
    private array $textMatchers = [];

    /** @return array<class-string<TextMatcherInterface>, TextMatcherInterface> */
    public function getTextMatchers(): array
    {
        return $this->textMatchers;
    }

    /**
     * @param  iterable<TextMatcherInterface|class-string<TextMatcherInterface>>  $textMatchers
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addTextMatchers(iterable $textMatchers): self
    {
        foreach ($textMatchers as $textMatcher) {
            $this->addTextMatcher($textMatcher);
        }

        return $this;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addTextMatcher(TextMatcherInterface|string $textMatcher): void
    {
        $resolved = $this->resolveTextMatcher($textMatcher);

        $this->textMatchers[$resolved::class] = $resolved;
    }

    /** @param  list<class-string<TextMatcherInterface>>  $names */
    public function removeTextMatchers(array $names): self
    {
        foreach ($names as $name) {
            $this->removeTextMatcher($name);
        }

        return $this;
    }

    /**
     * @param  string  $name  the matcher's fully qualified class name
     */
    public function removeTextMatcher(string $name): self
    {
        unset($this->textMatchers[$name]);

        return $this;
    }

    /**
     * @return class-string<TextMatcherInterface>|null the handled matcher's class, null if none took the message
     *
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function processTextMatchers(): ?string
    {
        $update = wHook()->update();

        if (! $update->isType('message')) {
            return null;
        }
        $message = $update->getMessage();
        if (! $message instanceof Message) {
            return null;
        }

        foreach ($this->textMatchers as $matcher) {
            if (! $matcher->isEnabled() || ! $matcher->matches($message)) {
                continue;
            }
            if (! hasAccess($matcher->getPerm())) {
                continue;
            }

            // Fresh instance: the registered one is a shared template.
            $this->handler($this->resolveTextMatcher($matcher::class));

            return $matcher::class;
        }

        return null;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    protected function handler(TextMatcherInterface $resolved): void
    {
        $currentState = wHook()->user()->state;
        $this->cancelOldProcess();
        $resolved->handle();
        stateAnswerBus()->cancelHandler($currentState);
    }
}
