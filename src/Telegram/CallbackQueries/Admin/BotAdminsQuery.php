<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\Features\BotAdminsFeature;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotAdminsQuery extends CallbackQuery
{
    protected string $type = 'BOTADMNS';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function addAdmin(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe::bot_admins.main.lock-keys.addingNewAdmin'));
        wHook()->user()->changeState(encodeAnswerState($this->type, "add_admin", ['message_meta_id' => $messageMeta->id]));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::bot_admins.main.text.enterNewAdminId'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $this->answer(__('tbe::bot_admins.main.answers.addingNewAdmin'));
    }

    /**
     * @throws TelegramSDKException
     */
    private function ownerInfo(): void
    {
        $text = __('tbe::bot_admins.main.answers.ownerInfo', [
            'ownerName' => wHook()->bot()->botOwner->full_name,
            'fromDate' => max(wHook()->bot()->created_at, wHook()->bot()->botOwner->created_at),
        ]);

        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => $text,
            'show_alert' => true,
            'cache_time' => 5,
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function deleteAdmin(BotUser $botUser): void
    {
        $botUser->power = 0;
        $botUser->save();

        BotAdminsFeature::menu()
            ->answer(__('tbe::bot_admins.main.answers.adminRemoved', [
                'adminName' => $botUser->telegramUser->full_name
            ]))
            ->update();
    }
}
