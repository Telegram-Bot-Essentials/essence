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

    public function handle(string $method): void
    {
        switch (strtolower($method)) {

        }
    }
}
