<?php

namespace TelegramBotEssentials\Essence\Support;

use BackedEnum;
use Stringable;

/**
 * A name shown in both the bot and the web panel. The text stays plain; the
 * bot puts its emoji in front, the panel draws its icon instead. As a string
 * it is the bot's wording, so code that expects a string label keeps working.
 */
final class Label implements Stringable
{
    public function __construct(
        public string $text,
        public ?string $emoji = null,
        public string|BackedEnum|null $icon = null,
    ) {}

    /** A plain string becomes a label with no emoji or icon. */
    public static function of(string|self $label): self
    {
        return $label instanceof self ? $label : new self($label);
    }

    /** "💰 Wallet credit" */
    public function forBot(): string
    {
        return $this->emoji === null || $this->emoji === '' ? $this->text : $this->emoji.' '.$this->text;
    }

    /** "Wallet credit" */
    public function forWeb(): string
    {
        return $this->text;
    }

    public function __toString(): string
    {
        return $this->forBot();
    }
}
