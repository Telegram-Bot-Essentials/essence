<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class MakeReplyKey extends GeneratorCommand
{
    protected $name = 'tbe:make:reply-key {name} {--perm=Member}';
    protected $description = 'Create a new ReplyKey class';
    protected $type = 'ReplyKey';

    protected function getDefaultNamespace($rootNamespace)
    {
        $guard = ucfirst($this->option('perm') ?? 'Member');

        if (!in_array($guard, ['Admin', 'Member'])) {
            throw new InvalidArgumentException('The --perm option must be either "Admin" or "Member".');
        }

        return $rootNamespace . '\\Telegram\\ReplyKeys\\' . $guard;
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $className = str_replace($this->getNamespace($name) . '\\', '', $name);
        $namespace = $this->getNamespace($name);

        $text = $this->generateTextFromName($this->argument('name'));
        $response = $text . ' executed successfully.';
        $translationKey = $this->generateTranslationKeyFromName($this->argument('name'));
        $perm = $this->getPermValue();
        $permPower = $perm == Roles::ADMIN->value ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';
        return str_replace(
            ['{{ namespace }}', '{{ class_name }}', '{{ text }}', '{{ response }}', '{{ translation_key }}', '{{ perm }}', '{{ perm_power }}'],
            [$namespace, $className, $text, $response, $translationKey, $perm, $permPower],
            $stub
        );
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/reply-key.stub';
    }

    protected function generateTextFromName(string $name): string
    {
        return ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)); // e.g., BuyService → Buy Service
    }

    protected function generateTranslationKeyFromName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)); // e.g., BuyService → buy_service
    }

    protected function getPermValue(): int
    {
        return match (strtolower($this->option('perm') ?? 'member')) {
            'admin' => 1,
            'member' => 0,
            default => throw new InvalidArgumentException('The --perm option must be either "Admin" or "Member".'),
        };
    }

    protected function getOptions()
    {
        return [
            ['perm', null, InputOption::VALUE_OPTIONAL, 'User permission level (Admin or Member)', 'Member'],
        ];
    }
}
