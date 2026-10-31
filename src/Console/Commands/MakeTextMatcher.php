<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeTextMatcher extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:text-matcher
        {name : The name of the text matcher}
        {--ability= : The Gate ability it asks for (none: open to everyone)}';

    protected $description = 'Create a new TextMatcher class';

    protected array $map = [];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/text-matcher.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Telegram\\TextMatchers';
    }

    protected function getNameInput(): string
    {
        return $this->initializeName().'Matcher';
    }

    protected function buildClass($name): string
    {
        $this->initializeValues();

        $stub = $this->files->get($this->getStub());

        return str_replace(
            ['{{ namespace }}', '{{ class_name }}', '{{ ability }}'],
            [
                $this->getNamespace($name),
                class_basename($name),
                $this->abilityLiteral(),
            ],
            $stub
        );
    }
}
