<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeReplyKey extends GeneratorCommand
{
    protected $name = 'tbe:make:reply-key';
    protected $description = 'Create a new ReplyKey class';
    protected $type = 'ReplyKey';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/reply-key.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $perm = ucfirst($this->option('perm') ?? 'Member');

        if (!in_array($perm, ['Admin', 'Member'], true)) {
            throw new InvalidArgumentException('The --perm option must be either "Admin" or "Member".');
        }

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
        $translationKey = $this->generateTranslationKeyFromName($inputName);
        $perm = $this->getPermValue();
        $permPower = $perm === Roles::ADMIN->value ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ text }}',
                '{{ response }}',
                '{{ translation_key }}',
                '{{ perm }}',
                '{{ perm_power }}',
            ],
            [
                $namespace,
                $className,
                $text,
                $response,
                $translationKey,
                $perm,
                $permPower,
            ],
            $stub
        );
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the ReplyKey class'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['perm', null, InputOption::VALUE_OPTIONAL, 'User permission level (Admin or Member)', 'Member'],
        ];
    }

    protected function generateTextFromName(string $name): string
    {
        return ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)); // BuyService → Buy Service
    }

    protected function generateTranslationKeyFromName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)); // BuyService → buy_service
    }

    protected function getPermValue(): int
    {
        return match (strtolower($this->option('perm') ?? 'member')) {
            'admin' => Roles::ADMIN->value,
            'member' => Roles::MEMBER->value,
            default => throw new InvalidArgumentException('The --perm option must be either "Admin" or "Member".'),
        };
    }
}
