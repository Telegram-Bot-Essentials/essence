<?php

namespace TelegramBotEssentials\Essence\Web\Telegram;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Web\LoginLinks;

/** Sends a one-time link that signs this user in to the manage panel in a browser. */
class PanelCommand extends Command
{
    protected string $name = 'panel';

    protected ?string $ability = 'web.manage';

    protected function description(): string
    {
        return (string) __('tbe-essence::web.panel_command');
    }

    public function handle(): ?bool
    {
        wHook()->api()->sendMessage([
            ...wHook()->replyTo(),
            'text' => (string) __('tbe-essence::web.panel_link_text'),
            'reply_markup' => Keyboard::make()->inline()->row([Keyboard::inlineButton([
                'text' => (string) __('tbe-essence::web.login_confirm'),
                'url' => app(LoginLinks::class)->create(wHook()->user(), 'manage'),
            ])]),
        ]);

        return true;
    }
}
