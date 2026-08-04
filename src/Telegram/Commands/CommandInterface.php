<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands;

interface CommandInterface
{
    public function getName(): string;

    public function getAliases(): array;

    public function getDescription(): string;

    public function getPerm(): int;

    public function isEnabled(): bool;

    public function setParams(array $params): void;

    public function handle(): ?bool;
}
