<?php

namespace TelegramBotEssentials\Essence\Support;

use Closure;

/**
 * A permission a package declares: its name is what handlers ask for and
 * what roles grant; the label is resolved per read so it follows the
 * active locale.
 */
final class Permission
{
    /**
     * @param  Closure(): string|string  $label  a translation key or a closure returning the label
     */
    public function __construct(
        public readonly string $name,
        public readonly Closure|string $label,
        public readonly string $group,
    ) {}

    public function label(): string
    {
        if ($this->label instanceof Closure) {
            return ($this->label)();
        }

        $translated = __($this->label);

        return is_string($translated) ? $translated : $this->label;
    }
}
