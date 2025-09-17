<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Telegram\Features\BotAdminsFeature;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotAdminsAnswer extends StateAnswer
{
    protected string $type = 'BOTADMNS';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param string $method
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(string $method): void
    {
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
}
