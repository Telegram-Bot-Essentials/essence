<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class MainMenuKey extends ReplyKey
{
    protected string $text = 'Main Menu';

    protected int $perm = 0;

    protected string $response = 'You are in the Main Menu';

    public function __construct()
    {
        $this->text = __('tbe::main_menu.reply_key');
        $this->response = __('tbe::main_menu.main.text.menu_changed');
    }

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        wHook()->user()->update(['menu' => 'main']);
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->response,
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
