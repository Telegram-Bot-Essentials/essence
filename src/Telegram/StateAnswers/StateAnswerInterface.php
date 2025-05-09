<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers;

interface StateAnswerInterface
{
    public function getType(): string;

    public function getPerm(): int;

    public function getAllowedFields(): array;

    public function handle(string $method, array $params): void;

    function cancel(): void;
}
