<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

/** Claims the same URLs as UrlTextMatcher, but only for users holding urls.admin. */
class AdminUrlTextMatcher extends UrlTextMatcher
{
    /** @var list<list<string>> */
    public static array $handled = [];

    protected ?string $permission = 'urls.admin';

    public function handle(): void
    {
        self::$handled[] = $this->urls();
    }
}
