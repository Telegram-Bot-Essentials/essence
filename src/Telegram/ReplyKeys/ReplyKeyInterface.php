<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys;

interface ReplyKeyInterface
{
    public function getText(): string;

    public function getPermission(): ?string;

    public function getResponse(): string;

    public function handle(): void;

    public function isEnabled(): bool;
}
