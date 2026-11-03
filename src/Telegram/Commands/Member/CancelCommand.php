<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands\Member;

use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

/**
 * Cancels the flow open in this chat. The controller already cancels a
 * conversation's flow before any command runs; this only confirms it. In a
 * group, where no Cancel reply key is shown, it is the typed way out.
 */
class CancelCommand extends Command
{
    protected string $name = 'cancel';

    protected ChatScope $chatScope = ChatScope::Any;

    protected function description(): string
    {
        return __('tbe::general.command.cancelDescription');
    }

    public function handle(): ?bool
    {
        wHook()->api()->sendMessage([
            ...wHook()->replyTo(),
            'text' => wHook()->requestState()
                ? __('tbe::cancel_process.main.text.response')
                : __('tbe::cancel_process.main.text.nothingToCancel'),
            'reply_markup' => replyKeyboard(),
        ]);

        return true;
    }
}
