<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotSettingsFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotSettingsKey extends ReplyKey
{
    protected string $text = 'Bot Settings';
    protected int $perm = Roles::ADMIN->value;

    public function __construct()
    {
        $this->text = __('tbe::bot_settings.reply_key');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        BotSettingsFeature::menuSend();
    }
}
