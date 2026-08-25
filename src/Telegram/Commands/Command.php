<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands;

abstract class Command implements CommandInterface
{
    protected string $name;

    protected array $aliases = [];

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

    /** The command's description, or null for none. Calls __() directly - a literal call, not a stored key, keeps IDE navigation/autocomplete/missing-key inspection working. */
    protected function description(): ?string
    {
        return null;
    }

    public function getDescription(): string
    {
        return $this->description() ?? '';
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
