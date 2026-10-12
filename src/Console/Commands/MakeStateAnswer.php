<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeStateAnswer extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:state-answer
        {name : The name of the feature}
        {--admin : Make feature for admin}
        {--permission= : The permission it asks for (default with --admin: app.<snake_name>)}
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
        return __DIR__.'/stubs/state-answer.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $scope = $this->option('admin') ? 'Admin' : 'Member';

        return $rootNamespace.'\\Telegram\\StateAnswers\\'.$scope;
    }

    protected function getNameInput(): string
    {
        return $this->initializeName().'Answer';
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
                '{{ permission }}',
            ],
            [
                $namespace,
                $className,
                $this->typeValue,
                $this->permissionLiteral(),
            ],
            $stub
        );
    }
}
