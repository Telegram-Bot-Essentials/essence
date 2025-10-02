<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class MessageMetaQuery extends CallbackQuery
{
    use CanCancelOldProcess;

    protected string $type = 'MESSAGE_META';
    protected int $perm = Roles::MEMBER->value;

    /**
     * @throws TelegramSDKException
     */
    private function actionIsLocked(): void
    {
        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => __('tbe::message_meta.main.answers.lockedForAction')
        ]);
    }

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    private function cancelAction(MessageMeta $messageMeta): void
    {
        $this->cancelOldProcess();
        $messageMeta->revertAction();
    }
}
