<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

use TelegramBotEssentials\Essence\Contracts\DeclaresChatScope;
use TelegramBotEssentials\Essence\Contracts\DeclaresPermission;
use TelegramBotEssentials\Essence\Traits\HasChatScope;
use TelegramBotEssentials\Essence\Traits\HasPermission;

abstract class ReplyKey implements DeclaresChatScope, DeclaresPermission, ReplyKeyInterface
{
    use HasChatScope;
    use HasPermission;

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
