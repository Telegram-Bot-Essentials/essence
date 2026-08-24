<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use TelegramBotEssentials\Essence\Traits\CanResolveReplyKey;

/**
 * Class CommandBus.
 */
class ReplyKeyBus
{
    use CanCancelOldProcess;
    use CanResolveReplyKey;

    private array $replyKeys = [];

    public function getReplyKeys(): array
    {
        return $this->replyKeys;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addReplyKeys(iterable $replyKeys): self
    {
        foreach ($replyKeys as $replyKey) {
            $this->addReplyKey($replyKey);
        }

        return $this;
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function addReplyKey(ReplyKeyInterface|string $replyKey): void
    {
        $resolved = $this->resolveReplyKey($replyKey);

        // Keyed by class, not by label: a label is locale-dependent, so
        // keying on it made the same key register once per locale and let
        // a bot in one language match a button labelled in another.
        $this->replyKeys[$resolved::class] = $resolved;
    }

    /**
     * Find the registered key whose label matches this text in the locale
     * active right now.
     *
     * @throws LogicException
     */
    private function findByText(string $text): ?ReplyKeyInterface
    {
        foreach ($this->replyKeys as $replyKey) {
            if ($replyKey->getText() === $text) {
                return $replyKey;
            }
        }

        return null;
    }

    public function removeReplyKeys(array $names): self
    {
        foreach ($names as $name) {
            $this->removeReplyKey($name);
        }

        return $this;
    }

    /**
     * @param  string  $name  the reply key's fully qualified class name
     */
    public function removeReplyKey(string $name): self
    {
        unset($this->replyKeys[$name]);

        return $this;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function processReplyKey(): bool
    {
        $update = wHook()->update();

        if (! $update->isType('message')) {
            return false;
        }
        $message = $update->getMessage();
        if (! $message->has('text')) {
            return false;
        }
        $text = $message->get('text');

        $key = $this->findByText($text);
        if ($key === null) {
            return false;
        }

        // Resolve a fresh instance to handle with: the registered one is a
        // long-lived template shared by every request on this worker.
        $resolvedKey = $this->resolveReplyKey($key::class);
        $this->handler($resolvedKey);

        return true;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    protected function handler(ReplyKeyInterface $resolvedKey): void
    {
        if (! $resolvedKey->isEnabled()) {
            return;
        }
        if (! hasAccess($resolvedKey->getPerm())) {
            return;
        }
        $currentState = wHook()->user()->state;
        $this->cancelOldProcess();
        $resolvedKey->handle();
        stateAnswerBus()->cancelHandler($currentState);
    }
}
