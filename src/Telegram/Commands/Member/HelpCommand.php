<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands\Member;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

class HelpCommand extends Command
{
    protected string $name = 'help';

    protected array $aliases = ['listcommands'];

    protected int $perm = Roles::MEMBER->value;

    protected string $descriptionKey = 'tbe::general.command.helpDescription';

    public function handle(): ?bool
    {
        $lines = [__('tbe::general.command.availableCommands')];

        foreach (commandBus()->getCommands() as $name => $command) {
            if (! $command->isEnabled()) {
                continue;
            }
            if (! hasAccess($command->getPerm())) {
                continue;
            }

            $lines[] = sprintf('/%s - %s', $name, $command->getDescription());
        }

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => implode(PHP_EOL, $lines),
        ]);

        return true;
    }
}
