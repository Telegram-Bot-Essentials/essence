<?php

namespace Elyar\TelegramBotEssentials\Telegram;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramResponse
{
    public function __construct(
        public ?string   $text = null,
        public ?Keyboard $replyMarkup = null,
        public ?string   $answer = null,
        public ?string   $parseMode = null,
    )
    {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            text: $data['text'] ?? '',
            replyMarkup: $data['reply_markup'] ?? null,
            answer: $data['answer'] ?? null,
            parseMode: $data['parse_mode'] ?? null,
        );
    }

    public function toEditMessageArray(int|string $chatId, int $messageId): array
    {
        return array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $this->text,
            'reply_markup' => $this->replyMarkup,
            'parse_mode' => $this->parseMode,
        ]);
    }

    public function toCallbackQueryAnswer(): array
    {
        return array_filter([
            'text' => $this->answer,
        ]);
    }

    public function answer(string $answer): self
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @throws TelegramSDKException
     */
    public function send(): void
    {
        wHook()->api()->sendMessage(
            array_filter([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $this->text,
                'reply_markup' => $this->replyMarkup,
                'parse_mode' => $this->parseMode,
            ])
        );
    }

    /**
     * @throws TelegramSDKException
     */
    public function update(): void
    {
        wHook()->api()->editMessageText(
            array_filter([
                'chat_id' => wHook()->update()->callbackQuery->message->chat->id,
                'message_id' => wHook()->update()->callbackQuery->message->messageId,
                'text' => $this->text,
                'reply_markup' => $this->replyMarkup,
                'parse_mode' => $this->parseMode,
            ])
        );
        if($this->answer) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $this->answer,
            ]);
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'replyMarkup' => $this->replyMarkup,
            'answer' => $this->answer,
            'parseMode' => $this->parseMode,
        ]);
    }
}