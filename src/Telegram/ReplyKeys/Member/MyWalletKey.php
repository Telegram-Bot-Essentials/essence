<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;

class MyWalletKey extends ReplyKey
{
    protected string $text = 'My Wallet';
    protected int $perm = Roles::MEMBER->value;
    protected string $response = 'My Wallet executed successfully.';

    public function __construct()
    {
        // Multilingual translations
        // $this->text = __('');
        // $this->response = __('');
    }

    public function handle(): void
    {
        // Logic to execute
    }
}
