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

    private function addAdmin()
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction("Adding new admin");
        wHook()->user()->changeState(encodeAnswerState($this->type, "add_admin", ['message_meta_id' => $messageMeta->id]));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Enter the new Admin's Telegram Username:",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $this->answer("Adding new admin...");
    }

    private function ownerInfo()
    {
        $text = wHook()->bot()->botOwner->full_name
            . " is owner of this bot from date " . max(wHook()->bot()->created_at, wHook()->bot()->botOwner->created_at);

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
        BotAdminsFeature::menuEdit();
        $this->answer("Admin \"" . $botUserAsAdmin->telegramUser->full_name . "\" removed");
    }
}
