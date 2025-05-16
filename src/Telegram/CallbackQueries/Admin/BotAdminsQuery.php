<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotAdminsFeature;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotAdminsQuery extends CallbackQuery
{
    protected string $type = 'BOTADMNS';
    protected int $perm = Roles::ADMIN->value;

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
            case 'add_admin':
                $this->addAdmin();
                break;
            case "owner_info":
                $this->ownerInfo();
                break;
            case "delete_admin":
                $this->deleteAdmin();
                break;
        }
    }

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
    private function deleteAdmin(): void
    {
        $botUserAsAdmin = wHook()->bot()->botUsers()->where('id', $this->params[1])->first();
        $botUserAsAdmin->power = 0;
        $botUserAsAdmin->save();

        BotAdminsFeature::menu()
            ->answer(__('tbe::bot_admins.main.answers.adminRemoved', [
                'adminName' => $botUserAsAdmin->telegramUser->full_name
            ]))
            ->update();
    }
}
