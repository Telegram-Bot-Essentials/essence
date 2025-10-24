<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use TelegramBotEssentials\Essence\Traits\CanResolveReplyKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Class CommandBus.
 */
class ReplyKeyBus
{
    use CanResolveReplyKey;
    use CanCancelOldProcess;
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
        $replyKey = $this->resolveReplyKey($replyKey);
        if(!$replyKey->isEnabled()) return;

        $this->replyKeys[$replyKey->getText()] = $replyKey;
    }

    public function removeReplyKeys(array $names): self
    {
        foreach ($names as $name) {
            $this->removeReplyKey($name);
        }

        return $this;
    }

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

        if (!$update->isType('message')) return false;
        $message = $update->getMessage();
        if (!$message->has('text')) return false;
        $text = $message->get('text');

        $key = $this->replyKeys[$text] ?? null;
        if (empty($key)) {
            return false;
        }

        $resolvedKey = $this->resolveReplyKey($key);
        $this->handler($resolvedKey);
        return true;
    }

    /**
     * @param ReplyKeyInterface $resolvedKey
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    protected function handler(ReplyKeyInterface $resolvedKey): void
    {
        if (!hasAccess($resolvedKey->getPerm())) return;
        $currentState = wHook()->user()->state;
        $this->cancelOldProcess();
        $resolvedKey->handle();
        stateAnswerBus()->cancelHandler($currentState);
    }
}
