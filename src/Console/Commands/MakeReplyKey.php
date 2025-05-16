<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Traits\TgObjectBuilder;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;

class MakeReplyKey extends GeneratorCommand
{
    use TgObjectBuilder;

    protected $name = 'tbe:make:reply-key';
    protected $description = 'Create a new ReplyKey class';
    protected $type = 'ReplyKey';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/reply-key.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = $this->validateInputPerm();
        return $rootNamespace . '\\Telegram\\ReplyKeys\\' . $perm;
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $className = class_basename($name);
        $namespace = $this->getNamespace($name);
        $inputName = $this->argument('name');

        $text = $this->generateTextFromName($inputName);
        $response = "$text executed successfully.";
        $perm = $this->getPermValue();
        $permPower = $perm === Roles::ADMIN->value ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';

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
                $permPower,
            ],
            $stub
        );
    }
}
