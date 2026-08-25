<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class MainMenuKey extends ReplyKey
{
    protected int $perm = 0;

    protected function text(): string
    {
        return __('tbe::main_menu.reply_key');
    }

    protected function response(): string
    {
        return __('tbe::main_menu.main.text.menu_changed');
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
            'text' => $this->getResponse(),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
