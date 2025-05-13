<?php

namespace Elyar\TelegramBotEssentials\Console\Commands;

use Illuminate\Console\GeneratorCommand;

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
        return $rootNamespace . '\\Telegram\\ReplyKeys\\Member';
    }
}
