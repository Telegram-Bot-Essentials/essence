<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Enums\Roles;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait TgObjectBuilder
{
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

    public function generateTypeFromName(string $name): string
    {
        return strtoupper(preg_replace([
        '/answer/',
        '/query/',
        '/feature/',
        '/callback/',
        '/key/',
    ], '', strtolower($name)));;
    }

    public function validateInputPerm(): string
    {
        $perm = ucfirst($this->option('perm') ?? 'Member');

        if (!in_array($perm, ['Admin', 'Member'], true)) {
            throw new InvalidArgumentException('The --perm option must be either "Admin" or "Member".');
        }

        return $perm;
    }
}
