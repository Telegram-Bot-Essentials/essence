<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeCommand extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:command
        {name : The class name}
        {--admin : Make command for admin}
        {--command-name= : The Telegram command name (default: snake_case of name)}
        {--d|description= : Command description}';

    protected $description = 'Create a new Telegram Bot Command class';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/command.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->option('admin') ? 'Admin' : 'Member';

        return $rootNamespace.'\\Telegram\\Commands\\'.$perm;
    }

    protected function buildClass($name): string
    {
        $this->initializeName();
        $this->initializeValues();
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);

        $commandNameOption = $this->option('command-name');
        $commandName = is_string($commandNameOption) && $commandNameOption !== ''
            ? $commandNameOption
            : strtolower((string) preg_replace('/Command$/', '', $className));

        $descriptionOption = $this->option('description');
        $description = is_string($descriptionOption) && $descriptionOption !== ''
            ? $descriptionOption
            : 'Command to handle '.$commandName;

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ command_name }}',
                '{{ description }}',
                '{{ perm_power }}',
            ],
            [
                $namespace,
                $className,
                $commandName,
                $description,
                $this->permEnumValue,
            ],
            $stub
        );
    }
}
