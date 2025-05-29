<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\InvalidPageNumber;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUsersAnswer extends StateAnswer
{
    protected string $type = 'BOTUSERS';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

    /**
     * @param string $method
     * @param array $params
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException|InvalidPageNumber
     */
    public function handle(string $method, array $params): void
    {
        $this->params = $params;
        switch (strtolower($method)) {
            case "cancel":
                $this->cancel();
                break;
            case "set_start_page":
                $this->setStartPage();
                break;
        }
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        if($messageMeta){
            $messageMeta->continueAction();
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws InvalidPageNumber
     */
    public function setStartPage(): void
    {
        $messageMeta = MessageMeta::findOrFail($this->params['message_meta_id']);
        $page = wHook()->update()->message->text;
        $lastPage = BotUser::paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = BotUsersFeature::start(intval($page));

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Page $page loaded",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $messageMeta->updateAndContinueAction($data);
    }
}
