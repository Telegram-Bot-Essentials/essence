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
        if (wHook()->conversation()->state) {
            $CancelProcessKey = $this->resolveReplyKey(CancelProcessKey::class);
            wHook()->conversation()->changeState();
            $text = (string) wHook()->update()?->message?->text;
            $isCancelRequest = $text === $CancelProcessKey->getText()
                || preg_match('~^/cancel(@\w+)?(\s|$)~i', $text) === 1;
            if (! $isCancelRequest) {
                wHook()->api()->sendMessage([
                    ...wHook()->replyTo(),
                    'text' => __('tbe::cancel_process.main.text.cancelDueToNewProcess'),
                    'reply_markup' => replyKeyboard(),
                ]);
            }
        }
    }
}
