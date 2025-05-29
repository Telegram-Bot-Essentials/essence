<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;

class BotUsersKey extends ReplyKey
{
    protected string $text = 'Bot Users 👥';
    protected int $perm = Roles::ADMIN->value;
    protected string $response = 'Bot Users executed successfully.';

    public function __construct()
    {
        // Multilingual translations
         $this->text = __('tbe::bot_users.reply_key');
        // $this->response = __('');
    }

    public function handle(): void
    {
        BotUsersFeature::start()->send();
    }
}
