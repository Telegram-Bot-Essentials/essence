<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;

class MyWalletQuery extends CallbackQuery
{
    protected string $type = 'MYWALLET';
    protected int $perm = Roles::MEMBER->value;

    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "start":
                // Use dependsOn() to give condition to check if the callback is allowed
                // dependsOn(false);
                $this->start();
                break;
        }
    }

    public function start(): void
    {
        // Logic to execute
    }
}
