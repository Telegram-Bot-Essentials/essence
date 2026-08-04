<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands;

abstract class Command implements CommandInterface
{
    protected string $name;

    protected array $aliases = [];

    protected string $description = '';

    protected int $perm;

    protected array $params = [];

    /** Maps a bound model's FQCN to the column its param value should be looked up by (default: "id"). */
    protected array $bindings = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    abstract public function handle(): ?bool;
}
