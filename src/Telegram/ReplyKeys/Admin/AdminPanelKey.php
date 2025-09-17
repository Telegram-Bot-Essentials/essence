<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

class AdminPanelKey extends ReplyKey
{
    protected string $text = 'Admin Panel';
    protected int $perm = 100;
    protected string $response = 'You are in the admin panel';

    public function __construct()
    {
        $this->text = __('tbe::admin_panel.reply_key');
        $this->response = __('tbe::admin_panel.main.text.menu_changed');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        wHook()->user()->update(['menu' => 'admin']);
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->response,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
