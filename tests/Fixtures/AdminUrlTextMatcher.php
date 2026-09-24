<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Enums\Roles;

/** Claims the same URLs as UrlTextMatcher, but only for admins. */
class AdminUrlTextMatcher extends UrlTextMatcher
{
    /** @var list<list<string>> */
    public static array $handled = [];

    protected int $perm = Roles::ADMIN->value;

    public function handle(): void
    {
        self::$handled[] = $this->urls();
    }
}
