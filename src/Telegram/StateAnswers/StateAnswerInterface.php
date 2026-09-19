<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Models\MessageMeta;

interface StateAnswerInterface
{
    public function getType(): string;

    public function getPerm(): int;

    public function getAllowedFields(): array;

    public function setParams(?array $params): void;

    public function setMethod(string $method): void;

    /**
     * The reply keyboard to show while a user is in this state, or null for
     * the default (a lone Cancel key). Called on an instance that already
     * carries the state's method and params, so a multi-step flow can offer
     * different keys per step.
     */
    public function keyboard(): ?Keyboard;

    public function handle(): void;

    public function messageMeta(): ?MessageMeta;

    public function cancel(): void;

    public function isEnabled(): bool;
}
