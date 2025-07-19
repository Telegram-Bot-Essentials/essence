<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers;

use Elyar\TelegramBotEssentials\Models\MessageMeta;

interface StateAnswerInterface
{
    public function getType(): string;
    public function getPerm(): int;
    public function getAllowedFields(): array;

    public function setParams(?array $params): void;

    public function handle(string $method): void;
    public function messageMeta(): ?MessageMeta;
    function cancel(): void;
}
