<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Traits\TgObjectBuilder;
use Illuminate\Console\GeneratorCommand;

class MakeFeature extends GeneratorCommand
{
    use TgObjectBuilder;

    protected $name = 'tbe:make:feature';
    protected $description = 'Create a new feature';
    protected $type = 'Feature';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/feature.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->validateInputPerm();
        return $rootNamespace . '\\Telegram\\Feature\\' . $perm;
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);
        $inputName = $this->argument('name');

        $type = $this->generateTypeFromName($inputName);

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ type }}',
            ],
            [
                $namespace,
                $className,
                $type,
            ],
            $stub
        );
    }
}
