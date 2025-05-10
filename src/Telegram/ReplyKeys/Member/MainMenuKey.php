<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member;

use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

class MainMenuKey extends ReplyKey
{
    protected string $text = 'Main Menu';
    protected int $perm = 0;
    protected string $response = 'You are in the Main Menu';

    public function __construct()
    {
        $this->text = __('reply_keys.mainMenu');
        $this->response = __('reply_keys.mainMenuResponse');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        wHook()->user()->update(['menu' => 'main']);
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->response,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
