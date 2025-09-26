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

    public function setParams(?array $params): void
    {
        $this->params = $params ?? [];
    }

    public function handle(): void
    {
        $command = strtolower($this->params[0] ?? '');

        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $command))));

        if (method_exists($this, $camel)) {
            $this->{$camel}();
        } elseif (method_exists($this, $command)) {
            $this->{$command}();
        } else {
            $this->answer();
        }
    }

    protected function answer(string $text = ""): void
    {
        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => $text
        ]);
    }
}
