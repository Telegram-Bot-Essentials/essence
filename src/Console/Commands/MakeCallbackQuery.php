<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Traits\TgObjectBuilder;
use Illuminate\Console\GeneratorCommand;

class MakeCallbackQuery extends GeneratorCommand
{
    use TgObjectBuilder;

    protected $name = 'tbe:make:callback-query';
    protected $description = 'Create a new CallbackQuery class';
    protected $type = 'CallbackQuery';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/callback-query.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->validateInputPerm();
        return $rootNamespace . '\\Telegram\\CallbackQuery\\' . $perm;
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);
        $inputName = $this->argument('name');

        $type = $this->generateTypeFromName($inputName);
        $perm = $this->getPermValue();
        $permPower = $perm === Roles::ADMIN->value ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ type }}',
                '{{ perm_power }}',
            ],
            [
                $namespace,
                $className,
                $type,
                $permPower,
            ],
            $stub
        );
    }
}
