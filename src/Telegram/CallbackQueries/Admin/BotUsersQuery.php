<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUsersQuery extends CallbackQuery
{
    protected string $type = 'BOTUSERS';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "start":
                $this->start();
                break;
            case "set_start_page":
                $this->setStartPage();
                break;

            case "show":
                $this->show();
                break;
            case "suspend":
                $this->suspend();
                break;
            case "role":
                $this->role();
                break;
        }
    }

    private function start()
    {
        $page = intval($this->params[1] ?? 1);
        $currentPage = intval($this->params[2] ?? 0);
        BotUsersFeature::start($page, $currentPage)->update();
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function setStartPage(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction('Waiting for page number');
        wHook()->user()->changeState(encodeAnswerState($this->type, "set_start_page", [
            "message_meta_id" => $messageMeta->id
        ]));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Enter page number:",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function show(): void
    {
        $botUser = BotUser::findOrFail($this->params[1]);
        $lastPage = intval($this->params[2] ?? 1);
        BotUsersFeature::show($botUser, $lastPage)->update();
    }

    private function suspend()
    {
        $botUser = BotUser::findOrFail($this->params[1]);
        $botUser->suspend = $this->params[2];
        $botUser->save();
        $lastPage = intval($this->params[3] ?? 1);

        BotUsersFeature::show($botUser, $lastPage)->update();
    }

    private function role()
    {
        $botUser = BotUser::findOrFail($this->params[1]);
        $roles = array_map(fn($role) => $role->value, Roles::cases());
        \Log::error(json_encode($roles));
        $next = getNextFromArray($roles, $botUser->power);
        \Log::error(json_encode($next));
        $botUser->power = $next ?? 0;
        $botUser->save();

        $lastPage = intval($this->params[2] ?? 1);
        BotUsersFeature::show($botUser, $lastPage)->update();
    }
}
