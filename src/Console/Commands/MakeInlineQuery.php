<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeInlineQuery extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:inline-query {name : The name of the inline query handler}';

    protected $description = 'Create a new InlineQuery handler class';

    protected array $map = [];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/inline-query.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Telegram\\InlineQueries';
    }

    protected function getNameInput(): string
    {
        return $this->initializeName().'InlineQuery';
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ perm_power }}',
            ],
            [
                $namespace,
                $className,
                'Roles::MEMBER->value',
            ],
            $stub
        );
    }
}
