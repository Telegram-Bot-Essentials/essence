<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries;

interface CallbackQueryInterface
{
    public function getType(): string;

    public function getPerm(): int;

    public function handle(array $params): void;
}
