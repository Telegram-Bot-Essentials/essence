<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

/** A public menu key, for tests that need a menu to show something. */
class SupportReplyKey extends ReplyKey
{
    protected function text(): string
    {
        return 'Support';
    }

    public function handle(): void {}
}
