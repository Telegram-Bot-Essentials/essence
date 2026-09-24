<?php

namespace TelegramBotEssentials\Essence\Telegram\TextMatchers;

use Telegram\Bot\Objects\Message;

/**
 * A handler for a free-form message that no reply key, command or state
 * answer claimed. Where a ReplyKey matches one exact button label, a
 * TextMatcher matches by rule: set `$pattern` for a plain regex tested
 * against the text (or the caption of a media message), or override
 * `matches()` for anything else (entities, attachments, a lookup...).
 */
abstract class TextMatcher implements TextMatcherInterface
{
    protected int $perm = 0;

    /** A regex tested against the message's text or caption by the default `matches()`. */
    protected ?string $pattern = null;

    public function matches(Message $message): bool
    {
        return $this->pattern !== null && preg_match($this->pattern, $this->text($message)) === 1;
    }

    abstract public function handle(): void;

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    private function currentMessage(): Message
    {
        $message = wHook()->update()->getMessage();

        return $message instanceof Message ? $message : new Message([]);
    }

    /**
     * The text of the message, or its caption for a media message; empty
     * when it has neither. Defaults to the message being handled.
     */
    protected function text(?Message $message = null): string
    {
        $message ??= $this->currentMessage();

        return (string) ($message->get('text') ?? $message->get('caption') ?? '');
    }

    /**
     * The URLs Telegram itself recognised in the message: bare `url`
     * entities as written, and `text_link` entities by their hidden target.
     * Trusting the entities beats a hand-rolled URL regex, which trips over
     * trailing punctuation, and it sees links whose visible text is a word.
     * A media message carries its entities under `caption_entities`.
     *
     * @return list<string>
     */
    protected function urls(?Message $message = null): array
    {
        $message ??= $this->currentMessage();

        $entities = $message->has('text') ? $message->get('entities') : $message->get('caption_entities');
        $utf16 = mb_convert_encoding($this->text($message), 'UTF-16LE', 'UTF-8');
        $urls = [];

        foreach ($entities ?? [] as $entity) {
            $type = data_get($entity, 'type');

            if ($type === 'text_link') {
                $urls[] = (string) data_get($entity, 'url');
            } elseif ($type === 'url') {
                // Entity offsets and lengths count UTF-16 code units.
                $slice = substr($utf16, (int) data_get($entity, 'offset') * 2, (int) data_get($entity, 'length') * 2);
                $urls[] = mb_convert_encoding($slice, 'UTF-8', 'UTF-16LE');
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }
}
