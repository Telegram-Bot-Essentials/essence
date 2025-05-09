<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers;

use Elyar\TelegramBotEssentials\Enums\AllowableFields;

abstract class StateAnswer implements StateAnswerInterface
{
    protected string $type;
    protected int $perm;
    protected array $params;
    protected array $allowedFields = [AllowableFields::TEXT->value];

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    abstract public function handle(string $method, array $params): void;

    abstract function cancel(): void;
}
