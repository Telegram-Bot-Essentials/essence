<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

interface ReplyKeyInterface
{
    public function getText(): string;

    public function getPerm(): int;

    public function getResponse(): string;

    public function handle(): void;
}
