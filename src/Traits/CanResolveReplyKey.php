<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

trait CanResolveReplyKey
{
    use CanBuildDependencyInjectedClass;
    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveReplyKey(ReplyKeyInterface|string $replyKey): ReplyKeyInterface
    {
        if (!is_a($replyKey, ReplyKeyInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'ReplyKey class "%s" should be an instance of "%s"',
                    is_object($replyKey) ? $replyKey::class : $replyKey,
                    ReplyKeyInterface::class
                )
            );
        }

        return $this->buildDependencyInjectedClass($replyKey);
    }
}
