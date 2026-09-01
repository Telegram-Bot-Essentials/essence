<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Support\Str;
use TelegramBotEssentials\Essence\Enums\Roles;

/**
 * Name-parsing and text-generation helpers shared by every `tbe:make:*`
 * command. Path/namespace resolution lives in PackageGeneratorCommand,
 * which every such command extends - not here.
 */
trait TgClassMaker
{
    private string $nameValue;

    private int $permValue;

    private string $permEnumValue;

    private string $typeValue;

    public function initializeName(): string
    {
        $argument = $this->argument('name');
        $name = trim(is_string($argument) ? $argument : '');

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }

        $stripped = preg_replace([
            '/answer/i',
            '/query/i',
            '/feature/i',
            '/callback/i',
            '/key/i',
            '/command/i',
        ], '', $name);

        $this->nameValue = is_string($stripped) ? $stripped : $name;

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
            if ($this->permValue == 100) {
                $arguments['--admin'] = true;
            }

            $this->call($command, $arguments);
        }
    }

    private function initializeValues(): void
    {
        $this->typeValue = strtoupper($this->nameValue);
        if ($this->hasOption('admin')) {
            $this->permValue = $this->option('admin') ? Roles::ADMIN->value : Roles::MEMBER->value;
            $this->permEnumValue = $this->option('admin') ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';
        }
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
