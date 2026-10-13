<?php

namespace TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class CancelProcessKey extends ReplyKey
{
    // Also the text a group's inline Cancel button replays (GroupKeyQuery).
    protected ChatScope $chatScope = ChatScope::Any;

    protected function text(): string
    {
        return __('tbe::cancel_process.reply_key');
    }

    protected function response(): string
    {
        return __('tbe::cancel_process.main.text.response');
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        wHook()->api()->sendMessage([
            ...wHook()->replyTo(),
            'text' => $this->getResponse(),
            'reply_markup' => replyKeyboard(),
        ]);
    }
}
