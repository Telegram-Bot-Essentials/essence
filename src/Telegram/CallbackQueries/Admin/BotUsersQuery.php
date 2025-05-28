<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;

class BotUsersQuery extends CallbackQuery
{
    protected string $type = 'BOTUSERS';
    protected int $perm = Roles::ADMIN->value;

    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "start":
                // Use dependsOn() to give condition to check if the callback is allowed
                // dependsOn(false);
                $this->start();
                break;
            case 'start_with_page':
                $this->startWithPage();
                break;
            case "show":
                $this->show();
                break;
                case 'start_with':
                $this->startWith();
                break;
        }
    }

    public function start(): void
    {
        $currentPage = intval($this->params[1]);
        $target = $this->params[2] ?? 'first';
        $lastPage = BotUser::paginate(perPage: 10)->lastPage();

        $error = TelegramPaginator::isOutOfBounds($target, $currentPage, $lastPage);
        if ($error) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $error,
                'show_alert' => true,
                'cache_time' => 5,
            ]);
            return;
        }

        $page = TelegramPaginator::getPage($target, $currentPage, $lastPage);
        BotUsersFeature::start($page)->update();
    }

    private function startWithPage()
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction('Waiting for page number');
        wHook()->user()->changeState(encodeAnswerState($this->type, "start_with_page", [
            "current_page" => $this->params[1],
            "message_meta_id" => $messageMeta->id
        ]));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Enter page number:"
        ]);
    }

    private function show()
    {
        $botUser = BotUser::findOrFail($this->params[1]);
        $page = intval($this->params[2]) ?? 1;
        BotUsersFeature::show($botUser, $page)->update();
    }

    private function startWith()
    {
        $page = intval($this->params[1]) ?? 1;
        BotUsersFeature::start($page)->update();
    }
}
