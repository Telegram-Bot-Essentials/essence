<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

interface CallbackQueryInterface
{
    public function getType(): string;

    public function getPermission(): ?string;

    public function setParams(?array $params): void;

    public function handle(): ?bool;

    public function isEnabled(): bool;
}
