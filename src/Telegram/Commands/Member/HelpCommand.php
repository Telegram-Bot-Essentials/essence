<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands\Member;

use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

class HelpCommand extends Command
{
    protected string $name = 'help';

    protected ChatScope $chatScope = ChatScope::Any;

    protected array $aliases = ['listcommands'];

    protected function description(): string
    {
        return __('tbe::general.command.helpDescription');
    }

    public function handle(): ?bool
    {
        $lines = [__('tbe::general.command.availableCommands')];

        foreach (commandBus()->getCommands() as $name => $command) {
            if (! $command->isEnabled()) {
                continue;
            }
            if (! canUseHandler($command)) {
                continue;
            }

            $lines[] = sprintf('/%s - %s', $name, $command->getDescription());
        }

        wHook()->api()->sendMessage([
            ...wHook()->replyTo(),
            'text' => implode(PHP_EOL, $lines),
        ]);

        return true;
    }
}
