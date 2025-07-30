<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Admin;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\Features\Admin\ManageInvoicesFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;

class ManageInvoicesKey extends ReplyKey
{
    protected string $text = 'Manage Invoices';
    protected int $perm = Roles::ADMIN->value;
    protected string $response = 'Manage Invoices executed successfully.';

    public function __construct()
    {
        // Multilingual translations
        // $this->text = __('');
        // $this->response = __('');
    }

    public function handle(): void
    {
        ManageInvoicesFeature::menu()->send();
        // Logic to execute
    }
}
