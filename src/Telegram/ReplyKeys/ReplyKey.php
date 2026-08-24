<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

use TelegramBotEssentials\Essence\Exceptions\LogicException;

abstract class ReplyKey implements ReplyKeyInterface
{
    /**
     * Translation key for the button's label.
     *
     * Held as a key rather than a resolved string so one instance serves
     * every locale. Resolving __() in a constructor froze the label to
     * whichever locale was active when the key was built, which is why
     * handlers had to be rebuilt on every request.
     */
    protected string $textKey;

    protected int $perm = 0;

    /** Translation key for the message sent when the button is pressed. */
    protected string $responseKey = '';

    /**
     * @throws LogicException
     */
    public function getText(): string
    {
        if (! isset($this->textKey)) {
            throw new LogicException(static::class.' must declare a $textKey.');
        }

        return (string) __($this->textKey);
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function getResponse(): string
    {
        return $this->responseKey === '' ? '' : (string) __($this->responseKey);
    }

    abstract public function handle(): void;

    public function isEnabled(): bool
    {
        return true;
    }
}
