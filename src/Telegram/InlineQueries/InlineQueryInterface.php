<?php

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

interface InlineQueryInterface
{
    public function getPermission(): ?string;

    public function handle(): void;

    public function isEnabled(): bool;
}
