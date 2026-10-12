<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeTextMatcher extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:text-matcher
        {name : The name of the text matcher}
        {--admin : Make the matcher admin-only}
        {--permission= : The permission it asks for (default with --admin: app.<snake_name>)}';

    protected $description = 'Create a new TextMatcher class';

    protected array $map = [];

    protected function getStub(): string
    {
        return __DIR__.'/stubs/text-matcher.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        // The scope folder is what the service provider scans at boot.
        $scope = $this->option('admin') ? 'Admin' : 'Member';

        return $rootNamespace.'\\Telegram\\TextMatchers\\'.$scope;
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
            ['{{ namespace }}', '{{ class_name }}', '{{ permission }}'],
            [
                $this->getNamespace($name),
                class_basename($name),
                $this->permissionLiteral(),
            ],
            $stub
        );
    }
}
