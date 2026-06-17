<?php

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

use Telegram\Bot\Exceptions\TelegramSDKException;

abstract class InlineQuery implements InlineQueryInterface
{
    protected int $perm;

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function query(): string
    {
        return wHook()->update()->inlineQuery->query ?? '';
    }

    protected function answer(array $results, array $extra = []): void
    {
        try {
            wHook()->api()->answerInlineQuery(array_merge([
                'inline_query_id' => wHook()->update()->inlineQuery->id,
                'results' => json_encode($results),
            ], $extra));
        } catch (TelegramSDKException) {
        }
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
