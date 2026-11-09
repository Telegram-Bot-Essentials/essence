<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeCommand extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:command
        {name : The class name}
        {--ability= : The Gate ability it asks for (none: open to everyone)}
        {--command-name= : The Telegram command name (default: snake_case of name)}
        {--d|description= : Command description}';

    protected $description = 'Create a new Telegram Bot Command class';

    /** @var array<string, string> none: a command generates nothing else */
    protected array $map = [];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/command.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Telegram\\Commands';
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
                '{{ ability }}',
            ],
            [
                $namespace,
                $className,
                $commandName,
                $description,
                $this->abilityLiteral(),
            ],
            $stub
        );
    }
}
