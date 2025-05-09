<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys;

interface ReplyKeyInterface
{
    public function getText(): string;

    public function getPerm(): int;

    public function getResponse(): string;

    public function handle(): void;
}
