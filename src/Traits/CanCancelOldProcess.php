<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member\CancelProcessKey;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait CanCancelOldProcess
{
    use CanResolveReplyKey;

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    function cancelOldProcess(): void
    {
        if (wHook()->user()->state) {
            $CancelProcessKey = $this->resolveReplyKey(CancelProcessKey::class);
            if(wHook()->update()?->message?->text !== $CancelProcessKey->getText()){
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => __('telegram-bot-essentials::cancel_process.main.text.cancelDueToNewProcess'),
                    'reply_markup' => wHook()->user()->getKeyboard()
                ]);
            }
            wHook()->user()->changeState();
        }
    }
}
