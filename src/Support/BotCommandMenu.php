<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Traits\CanResolveBotCommand;

/**
 * Publishes the configured command list to a bot's Telegram menu.
 */
class BotCommandMenu
{
    use CanResolveBotCommand;

    public function register(Bot $bot): void
    {
        $previousLocale = App::getLocale();
        App::setLocale(app(ResolvesBotLocale::class)->resolve($bot));

        try {
            $commands = [];
            foreach (config('tbe-essence.commands') as $command) {
                $command = $this->resolveBotCommand($command);
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
