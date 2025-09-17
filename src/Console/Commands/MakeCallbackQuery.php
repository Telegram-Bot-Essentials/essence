<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;
use Illuminate\Console\GeneratorCommand;

class MakeCallbackQuery extends GeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:callback-query
        {name : The name of the feature}
        {--admin : Make feature for admin}
        {--a|all : Generate all types}
        {--f|feature : Generate feature class}
        {--r|reply-key : Generate reply key}
        {--s|state-answer : Generate state answer}';
    protected $description = 'Create a new CallbackQuery class';

    protected array $map = [
        'feature' => 'tbe:make:feature',
        'reply-key' => 'tbe:make:reply-key',
        'state-answer' => 'tbe:make:state-answer',
    ];

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/callback-query.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->option('admin') ? 'Admin' : 'Member';
        return $rootNamespace . '\\Telegram\\CallbackQueries\\' . $perm;
    }

    protected function getNameInput(): string
    {
        return $this->initializeName() . 'Query';
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
