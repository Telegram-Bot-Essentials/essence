<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeReplyKey extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:reply-key
        {name : The name of the feature}
        {--admin : Make feature for admin}
        {--a|all : Generate all types}
        {--f|feature : Generate feature class}
        {--c|callback : Generate callback query}
        {--s|state-answer : Generate state answer}';

    protected $description = 'Create a new ReplyKey class';

    protected array $map = [
        'feature' => 'tbe:make:feature',
        'callback' => 'tbe:make:callback-query',
        'state-answer' => 'tbe:make:state-answer',
    ];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/reply-key.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->option('admin') ? 'Admin' : 'Member';

        return $rootNamespace.'\\Telegram\\ReplyKeys\\'.$perm;
    }

    protected function getNameInput(): string
    {
        return $this->initializeName().'Key';
    }

    protected function buildClass($name): string
    {
        $this->initializeValues();
        $this->handleOptions();

        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);

        $text = $this->generateTextFromName($this->nameValue);
        $response = "$text executed successfully.";

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ text }}',
                '{{ response }}',
                '{{ perm_power }}',
            ],
            [
                $namespace,
                $className,
                $text,
                $response,
                $this->permEnumValue,
            ],
            $stub
        );
    }
}
