<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

interface CallbackQueryInterface
{
    public function getType(): string;

    public function getPerm(): int;

    public function handle(): void;
}
