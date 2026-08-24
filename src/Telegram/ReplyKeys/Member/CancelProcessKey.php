<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class CancelProcessKey extends ReplyKey
{
    protected string $textKey = 'tbe::cancel_process.reply_key';

    protected int $perm = 0;

    protected string $responseKey = 'tbe::cancel_process.main.text.response';

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $this->getResponse(),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
