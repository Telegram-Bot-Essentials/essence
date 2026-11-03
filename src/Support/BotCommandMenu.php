<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Facades\App;
use Telegram\Bot\Api;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Models\Bot;

/**
 * Publishes the configured command list to a bot's Telegram menu: one list
 * for private chats and one for group chats. Each holds the commands that run
 * there (see ChatScope).
 */
class BotCommandMenu
{
    public function register(Bot $bot): void
    {
        $previousLocale = App::getLocale();
        App::setLocale(app(ResolvesBotLocale::class)->resolve($bot));

        try {
            $private = [];
            $groups = [];
            foreach (commandBus()->getCommands() as $command) {
                if (! $command->isEnabled() || ! isPublicHandler($command)) {
                    continue;
                }

                $entry = [
                    'command' => $command->getName(),
                    'description' => $command->getDescription(),
                ];

                $scope = handlerChatScope($command);
                if ($scope !== ChatScope::Groups) {
                    $private[] = $entry;
                }
                if ($scope !== ChatScope::Private) {
                    $groups[] = $entry;
                }
            }
        } finally {
            App::setLocale($previousLocale);
        }

        $api = telegramApi($bot->bot_token);

        // Earlier versions sent a scope Telegram could not read, which set the
        // default list: it would otherwise keep showing up in groups.
        $api->deleteMyCommands();

        $this->publish($api, $private, 'all_private_chats');

        $this->publish($api, $groups, 'all_group_chats');
    }

    /** @param  array<int, array{command: string, description: string}>  $commands */
    private function publish(Api $api, array $commands, string $scope): void
    {
        $api->setMyCommands([
            'commands' => $commands,
            // The API takes this as JSON; the client would form-encode an array.
            'scope' => json_encode(['type' => $scope]),
        ]);
    }
}
