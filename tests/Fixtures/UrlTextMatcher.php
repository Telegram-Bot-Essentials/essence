<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Telegram\TextMatchers\TextMatcher;

class UrlTextMatcher extends TextMatcher
{
    /** @var list<list<string>> */
    public static array $handled = [];

    protected ?string $pattern = '~^https?://\S+$~i';

    public function handle(): void
    {
        self::$handled[] = $this->urls();
    }
}
