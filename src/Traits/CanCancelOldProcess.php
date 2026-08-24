<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;

trait CanCancelOldProcess
{
    use CanResolveReplyKey;

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function cancelOldProcess(): void
    {
        if (wHook()->user()->state) {
            $CancelProcessKey = $this->resolveReplyKey(CancelProcessKey::class);
            wHook()->user()->changeState();
            if (wHook()->update()?->message?->text !== $CancelProcessKey->getText()) {
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => __('tbe::cancel_process.main.text.cancelDueToNewProcess'),
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            }
        }
    }
}
