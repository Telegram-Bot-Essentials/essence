<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Elyar\TelegramBotEssentials\Telegram\Feature\BotAdminsFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotAdminsAnswer extends StateAnswer
{
    protected string $type = 'BOTADMNS';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @param array $params
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(string $method, array $params): void
    {
        $this->params = $params;
        switch (strtolower($method)) {
            case "add_admin":
                $this->addAdmin();
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
        $telegramUser = str_starts_with(wHook()->update()->message->text, '@') ?
            TelegramUser::where('username', str_replace('@', '', wHook()->update()->message->text))->firstOrFail():
            TelegramUser::where('peer_id', wHook()->update()->message->text)->firstOrFail();
        $botUser = wHook()->bot()->botUsers()->where('telegram_user_peer_id', $telegramUser->peer_id)->firstOrFail();
        $botUser->power = Roles::ADMIN->value;
        $botUser->save();

        $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe::bot_admins.main.text.adminAddedSuccessfully'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $data = BotAdminsFeature::menu();
        $messageMeta->updateAndContinueAction($data);
    }

    function cancel(): void
    {
        // TODO: Implement cancel() method.
    }
}
