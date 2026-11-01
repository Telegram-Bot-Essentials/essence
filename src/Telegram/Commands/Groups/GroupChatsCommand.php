<?php

namespace TelegramBotEssentials\Essence\Telegram\Commands\Groups;

use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\Features\Groups\GroupChatsFeature;

class GroupChatsCommand extends Command
{
    protected string $name = 'groups';

    protected ?string $ability = 'groups.manage';

    protected function description(): ?string
    {
        return __('tbe::group_chats.command_description');
    }

    public function isEnabled(): bool
    {
        return config()->boolean('tbe-essence.groups.enabled', false);
    }

    public function handle(): ?bool
    {
        GroupChatsFeature::index()->send();

        return true;
    }
}
