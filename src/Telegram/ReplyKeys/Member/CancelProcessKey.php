<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CancelProcessKey extends ReplyKey
{
    protected string $text = 'Cancel Process';
    protected int $perm = 0;

    public function __construct()
    {
        $this->text = __('cancel_process.reply_key');
        $this->response = __('cancel_process.main.text.response');
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
//        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->response,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
