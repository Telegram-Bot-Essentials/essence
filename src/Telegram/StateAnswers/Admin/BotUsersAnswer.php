<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\Feature\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUsersAnswer extends StateAnswer
{
    protected string $type = 'BOTUSERS';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

    public function handle(string $method, array $params): void
    {
        $this->params = $params;
        switch (strtolower($method)) {
            case "cancel":
                $this->cancel();
                break;
            case "start_with_page":
                $this->startWithPage();
                break;
        }
    }

    function cancel(): void
    {
        // TODO: Implement cancel() method.
        // Logic to revert the process if user cancels action
    }

    /**
     * @throws TelegramSDKException
     */
    public function startWithPage(): void
    {
        $currentPage = intval($this->params['current_page']);
        $messageMeta = MessageMeta::findOrFail($this->params['message_meta_id']);
        $page = wHook()->update()->message->text;
        $lastPage = BotUser::paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $messageMeta->updateAndContinueAction(BotUsersFeature::start(intval($page)));
    }
}
