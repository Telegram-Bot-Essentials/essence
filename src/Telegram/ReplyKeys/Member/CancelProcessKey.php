<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CancelProcessKey extends ReplyKey
{
    protected string $text = 'Cancel Process';
    protected int $perm = 0;

    public function __construct()
    {
        $this->text = __('tbe::cancel_process.reply_key');
        $this->response = __('tbe::cancel_process.main.text.response');
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->response,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
