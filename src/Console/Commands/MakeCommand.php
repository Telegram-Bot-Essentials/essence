<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;
use Illuminate\Console\GeneratorCommand;

class MakeCommand extends GeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:command
        {name : The class name}
        {--command-name= : The Telegram command name (default: snake_case of name)}
        {--p|pattern="{username}" : The pattern for arguments}
        {--d|description= : Command description}';

    protected $description = 'Create a new Telegram Bot Command class';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/command.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Telegram\\Commands';
    }

    protected function buildClass($name): string
    {
        $this->initializeName();
        $this->initializeValues();
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);

        $commandName = $this->option('command-name') ?? strtolower(preg_replace('/Command$/', '', $className));
        $description = $this->option('description') ?? 'Command to handle ' . $commandName;

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ command_name }}',
                '{{ description }}',
            ],
            [
                $namespace,
                $className,
                $commandName,
                $description,
            ],
            $stub
        );
    }

}
