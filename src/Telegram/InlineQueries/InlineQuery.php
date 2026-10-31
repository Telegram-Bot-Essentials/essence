<?php

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Contracts\DeclaresAbility;
use TelegramBotEssentials\Essence\Contracts\DeclaresChatScope;
use TelegramBotEssentials\Essence\Traits\HasAbility;
use TelegramBotEssentials\Essence\Traits\HasChatScope;

abstract class InlineQuery implements DeclaresAbility, DeclaresChatScope, InlineQueryInterface
{
    use HasAbility;
    use HasChatScope;

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
