<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Illuminate\Support\Str;

trait TgClassMaker
{
    private string $nameValue;
    private int $permValue;
    private string $permEnumValue;
    private string $typeValue;

    public function initializeName(): string
    {
        $name = trim($this->argument('name'));

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }

        $this->nameValue = preg_replace([
            '/answer/i',
            '/query/i',
            '/feature/i',
            '/callback/i',
            '/key/i',
        ], '', $name);

        return $this->nameValue;
    }

    private function handleOptions(): void
    {
        $types = [];
        if ($this->option('all')) {
            $types = array_keys($this->map);
        } else {
            foreach ($this->map as $option => $command) {
                if ($this->option($option)) {
                    $types[] = $option;
                }
            }
        }

        foreach ($types as $type) {
            $command = $this->map[$type];

            $arguments = [
                'name' => $this->nameValue,
            ];
            if ($this->permValue == 100) $arguments['--admin'] = true;

            $this->call($command, $arguments);
        }
    }

    private function initializeValues(): void
    {
        $this->typeValue = strtoupper($this->nameValue);
        $this->permValue = $this->option('admin') ? Roles::ADMIN->value : Roles::MEMBER->value;
        $this->permEnumValue = $this->option('admin') ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';
    }

    protected function generateTextFromName(string $name): string
    {
        return ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)); // BuyService → Buy Service
    }

    protected function generateTranslationKeyFromName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)); // BuyService → buy_service
    }
}
