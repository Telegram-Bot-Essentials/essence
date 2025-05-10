<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

class AdminPanelKey extends ReplyKey
{
    protected string $text = 'Admin Panel';
    protected int $perm = 100;
    protected string $response = 'You are in the admin panel';

    public function __construct()
    {
        $this->text = __('reply_keys.adminPanel');
        $this->response = __('reply_keys.adminPanelResponse');
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
