<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

abstract class ReplyKey implements ReplyKeyInterface
{
    protected int $perm = 0;

    /**
     * The button's label, calling __() directly rather than storing a key
     * property - a bare string property doesn't get IDE navigation/
     * autocomplete/missing-key inspection, a literal __() call does. Called
     * per read rather than resolved once so one instance serves every
     * locale: resolving it in a constructor would freeze the label to
     * whichever locale was active when the singleton was built.
     */
    abstract protected function text(): string;

    /** The message sent when the button is pressed, or null for none. */
    protected function response(): ?string
    {
        return null;
    }

    public function getText(): string
    {
        return $this->text();
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function getResponse(): string
    {
        return $this->response() ?? '';
    }

    abstract public function handle(): void;

    public function isEnabled(): bool
    {
        return true;
    }
}
