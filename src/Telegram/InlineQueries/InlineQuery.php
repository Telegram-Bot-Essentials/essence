<?php

namespace TelegramBotEssentials\Essence\Telegram\InlineQueries;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Contracts\DeclaresChatScope;
use TelegramBotEssentials\Essence\Contracts\DeclaresPermission;
use TelegramBotEssentials\Essence\Traits\HasChatScope;
use TelegramBotEssentials\Essence\Traits\HasPermission;

abstract class InlineQuery implements DeclaresChatScope, DeclaresPermission, InlineQueryInterface
{
    use HasChatScope;
    use HasPermission;

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
