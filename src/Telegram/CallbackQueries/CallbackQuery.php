<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

abstract class CallbackQuery implements CallbackQueryInterface
{
    protected string $type;
    protected int $perm;
    protected array $params;

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    abstract public function handle(array $params): void;

    protected function answer(string $text): void
    {
        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => $text
        ]);
    }
}
