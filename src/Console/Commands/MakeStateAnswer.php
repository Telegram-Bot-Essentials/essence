<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Traits\TgClassMaker;
use Illuminate\Console\GeneratorCommand;

class MakeStateAnswer extends GeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:state-answer
        {name : The name of the feature}
        {--admin : Make feature for admin}
        {--a|all : Generate all types}
        {--f|feature : Generate feature class}
        {--c|callback : Generate callback query}
        {--r|reply-key : Generate reply key}';
    protected $description = 'Create a new StateAnswer class';

    protected array $map = [
        'feature' => 'tbe:make:feature',
        'callback' => 'tbe:make:callback-query',
        'reply-key' => 'tbe:make:reply-key',
    ];

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/state-answer.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->option('admin') ? 'Admin' : 'Member';
        return $rootNamespace . '\\Telegram\\StateAnswers\\' . $perm;
    }

    protected function getNameInput(): string
    {
        return $this->initializeName() . 'Answer';
    }

    protected function buildClass($name): string
    {
        $this->initializeValues();
        $this->handleOptions();

        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);

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
                $this->typeValue,
                $this->permEnumValue,
            ],
            $stub
        );
    }
}
