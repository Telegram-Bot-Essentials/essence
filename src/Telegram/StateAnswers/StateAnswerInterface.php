<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use TelegramBotEssentials\Essence\Models\MessageMeta;

interface StateAnswerInterface
{
    public function getType(): string;
    public function getPerm(): int;
    public function getAllowedFields(): array;

    public function setParams(?array $params): void;

    public function handle(): void;
    public function messageMeta(): ?MessageMeta;
    function cancel(): void;
}
