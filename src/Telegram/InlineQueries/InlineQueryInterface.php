<?php

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

interface InlineQueryInterface
{
    public function getPerm(): int;

    public function handle(): void;

    public function isEnabled(): bool;
}
