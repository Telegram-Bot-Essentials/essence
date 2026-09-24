<?php

namespace TelegramBotEssentials\Essence\Telegram\TextMatchers;

use Telegram\Bot\Objects\Message;

interface TextMatcherInterface
{
    /**
     * Whether this matcher wants the given message. It sees the whole
     * message, so it can judge by text, caption, entities or attachments.
     */
    public function matches(Message $message): bool;

    public function getPerm(): int;

    public function handle(): void;

    public function isEnabled(): bool;
}
