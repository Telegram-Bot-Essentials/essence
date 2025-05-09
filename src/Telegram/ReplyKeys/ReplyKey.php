<?php

namespace Elyar\TelegramBotEssentials\Telegram\ReplyKeys;

abstract class ReplyKey implements ReplyKeyInterface
{
    protected string $text;
    protected int $perm = 0;
    protected string $response = "";

    public function getText(): string
    {
        return $this->text;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    abstract public function handle(): void;
}
