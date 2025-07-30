<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\Features\BotAdminsFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotAdminsKey extends ReplyKey
{
    protected string $text = 'Bot Settings';
    protected int $perm = Roles::ADMIN->value;

    public function __construct()
    {
        $this->text = __('tbe::bot_admins.reply_key');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        BotAdminsFeature::menu()->send();
    }
}
