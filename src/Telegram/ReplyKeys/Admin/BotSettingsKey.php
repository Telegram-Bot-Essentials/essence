<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;

class BotSettingsKey extends ReplyKey
{
    protected string $text = 'Bot Settings';
    protected int $perm = 100;

    public function __construct()
    {
        $this->text = __('telegram-bot-essentials::bot_settings.reply_key');
    }

    /**
     */
    public function handle(): void
    {
        BotSettingsFeature::menuSend();
    }
}
