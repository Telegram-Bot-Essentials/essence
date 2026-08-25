<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\Features\BotAdminsFeature;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class BotAdminsKey extends ReplyKey
{
    protected int $perm = Roles::ADMIN->value;

    protected function text(): string
    {
        return __('tbe::bot_admins.reply_key');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        BotAdminsFeature::menu()->send();
    }
}
