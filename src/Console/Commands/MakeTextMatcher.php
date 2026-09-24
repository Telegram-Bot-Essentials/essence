<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use TelegramBotEssentials\Essence\Traits\TgClassMaker;

class MakeTextMatcher extends PackageGeneratorCommand
{
    use TgClassMaker;

    protected $signature = 'tbe:make:text-matcher
        {name : The name of the text matcher}
        {--admin : Make the matcher admin-only}';

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
        $stub = $this->files->get($this->getStub());

        return str_replace(
            ['{{ namespace }}', '{{ class_name }}', '{{ perm_power }}'],
            [
                $this->getNamespace($name),
                class_basename($name),
                $this->option('admin') ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value',
            ],
            $stub
        );
    }
}
