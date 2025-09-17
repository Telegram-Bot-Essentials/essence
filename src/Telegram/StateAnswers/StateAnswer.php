<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\StateData;

abstract class StateAnswer implements StateAnswerInterface
{
    protected string $type;
    protected int $perm;
    protected array $params;
    protected ?MessageMeta $messageMeta = null;
    protected ?StateData $stateData = null;

    protected array $allowedFields = [AllowableFields::TEXT->value];

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function setParams(?array $params): void
    {
        $this->params = $params ?? [];
    }

    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    abstract public function handle(string $method): void;

    public function messageMeta(): ?MessageMeta
    {
        if ($this->messageMeta) return $this->messageMeta;
        $messageMeta = MessageMeta::find($this->params['message_meta_id'] ?? ($this->params['message_meta'] ?? null));
        if (!$messageMeta) {
            exceptionReport(new TbeLogicException('Trying to access message meta, but it is not provided'));
            return null;
        }
        $this->messageMeta = $messageMeta;
        return $messageMeta;
    }

    public function stateData(): ?StateData
    {
        if ($this->stateData) return $this->stateData;
        $stateData = StateData::find($this->params['state_data_id'] ?? $this->params['state_data']);
        if (!$stateData) {
            exceptionReport(new TbeLogicException('Trying to access state data, but it is not provided'));
            return null;
        }
        $this->stateData = $stateData;
        return $stateData;
    }

    public function cancel(): void
    {
        $this->messageMeta()?->revertAction();
    }
}
