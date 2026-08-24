<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class AdminPanelKey extends ReplyKey
{
    protected string $textKey = 'tbe::admin_panel.reply_key';

    protected int $perm = 100;

    protected string $responseKey = 'tbe::admin_panel.main.text.menu_changed';

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        wHook()->user()->update(['menu' => 'admin']);
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->getResponse(),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
