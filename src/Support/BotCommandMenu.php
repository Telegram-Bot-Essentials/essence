<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\Bot;

/**
 * Publishes the configured command list to a bot's Telegram menu.
 */
class BotCommandMenu
{
    public function register(Bot $bot): void
    {
        $previousLocale = App::getLocale();
        App::setLocale(app(ResolvesBotLocale::class)->resolve($bot));

        try {
            $commands = [];
            foreach (commandBus()->getCommands() as $command) {
                if (! $command->isEnabled() || $command->getPerm() > Roles::MEMBER->value) {
                    continue;
                }

                $commands[] = [
                    'command' => $command->getName(),
                    'description' => $command->getDescription(),
                ];
            }
        } finally {
            App::setLocale($previousLocale);
        }

        telegramApi($bot->bot_token)->setMyCommands([
            'commands' => $commands,
            'scope' => [
                'type' => 'all_private_chats',
            ],
        ]);
    }
}
