<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

/** Matches the same URLs as UrlTextMatcher, to prove registration order decides. */
class SecondUrlTextMatcher extends UrlTextMatcher
{
    /** @var list<list<string>> */
    public static array $handled = [];

    public function handle(): void
    {
        self::$handled[] = $this->urls();
    }
}
