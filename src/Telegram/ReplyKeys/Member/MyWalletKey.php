<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Telegram\Feature\Member\MyWalletFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;

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

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        MyWalletFeature::main()->send();
    }
}
