<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Traits\CanCancelOldProcess;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class MessageMetaQuery extends CallbackQuery
{
    use CanCancelOldProcess;

    protected string $type = 'MESSAGE_META';
    protected int $perm = Roles::MEMBER->value;

    /**
     * @param array $params
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "action_is_locked":
                $this->actionIsLocked();
                break;
            case "cancel_action":
                $this->cancelAction();
                break;
        }
    }

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
    private function cancelAction(): void
    {
        $this->cancelOldProcess();
        $messageMeta = MessageMeta::findOrFail($this->params[1]);
        $messageMeta->revertAction();
    }
}
