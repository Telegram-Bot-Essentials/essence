<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class MyWalletQuery extends CallbackQuery
{
    protected string $type = 'MYWALLET';
    protected int $perm = Roles::MEMBER->value;

    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "add_credit":
                $this->addCredit();
                break;
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addCredit(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->deleteMessage();
        wHook()->user()->changeState(encodeAnswerState($this->type, "add_credit"));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Enter credit amount to add:",
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }
}
