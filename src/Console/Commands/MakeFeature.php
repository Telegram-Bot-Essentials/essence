<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeFeature extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:feature
        {name : The name of the feature}
        {--admin : Make object for admin}
        {--permission= : The permission it asks for (default with --admin: app.<snake_name>)}
        {--a|all : Generate all types}
        {--c|callback : Generate callback query}
        {--r|reply-key : Generate reply key}
        {--s|state-answer : Generate state answer}';

    protected $description = 'Create a new feature';

    protected array $map = [
        'callback' => 'tbe:make:callback-query',
        'reply-key' => 'tbe:make:reply-key',
        'state-answer' => 'tbe:make:state-answer',
    ];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/feature.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $scope = $this->option('admin') ? 'Admin' : 'Member';

        return $rootNamespace.'\\Telegram\\Features\\'.$scope;
    }

    protected function getNameInput(): string
    {
        return $this->initializeName().'Feature';
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
            ],
            [
                $namespace,
                $className,
                $this->typeValue,
            ],
            $stub
        );
    }
}
