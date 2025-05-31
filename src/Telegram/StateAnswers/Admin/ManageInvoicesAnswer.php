<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;

class ManageInvoicesAnswer extends StateAnswer
{
    protected string $type = 'MANAGEINVOICES';
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
        }
    }

    function cancel(): void
    {
        // TODO: Implement cancel() method.
        // Logic to revert the process if user cancels action

        // example:
        // $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        // if($messageMeta){
        //     $messageMeta->continueAction();
        // }
    }
}
