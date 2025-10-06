<?php

namespace TelegramBotEssentials\Essence\Telegram;

use TelegramBotEssentials\Essence\Models\MessageMeta;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Message;

class TelegramResponse
{
    private ?Model $modelForMessageMeta = null;
    private ?string $messageMetaTag = null;
    private ?string $softAnswer = null;

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

    public function softAnswer(string $answer): self
    {
        $this->softAnswer = $answer;
        return $this;
    }

    public function messageMetaModel(Model $model, ?string $tag = null): self
    {
        $this->modelForMessageMeta = $model;
        $this->messageMetaTag = $tag;
        return $this;
    }

    public function messageMetaTag(?string $tag = null): self
    {
        $this->messageMetaTag = $tag;
        return $this;
    }

    /**
     * @throws TelegramSDKException
     */
    public function send(string|int|null $chatId = null): Message
    {
        $message = wHook()->api()->sendMessage(
            array_filter([
                'chat_id' => $chatId ?? wHook()->user()->telegramUser->peer_id,
                'text' => $this->text,
                'reply_markup' => $this->replyMarkup,
                'parse_mode' => $this->parseMode,
            ])
        );

        if ($this->modelForMessageMeta) {
            $messageMeta = MessageMeta::makeWithMessage($message, $this->messageMetaTag);
            $messageMeta->action()->associate($this->modelForMessageMeta);
            $messageMeta->save();
        }

        return $message;
    }

    /**
     * @throws TelegramSDKException
     */
    public function update(string|int|null $chatId = null, string|int|null $messageId = null): void
    {
        try {
            if ($this->text) {
                try {
                    $message = wHook()->api()->editMessageText(
                        array_filter([
                            'chat_id' => $chatId ?? wHook()->update()->callbackQuery->message->chat->id,
                            'message_id' => $messageId ?? wHook()->update()->callbackQuery->message->messageId,
                            'text' => $this->text,
                            'reply_markup' => $this->replyMarkup,
                            'parse_mode' => $this->parseMode,
                        ])
                    );

                    if ($this->modelForMessageMeta) {
                        $messageMeta = MessageMeta::makeWithMessage($message, $this->messageMetaTag);
                        $messageMeta->action()->associate($this->modelForMessageMeta);
                        $messageMeta->save();
                    }
                } catch (Exception $e) {
                    exceptionReport($e);
                    wHook()->api()->answerCallbackQuery([
                        'callback_query_id' => wHook()->update()->callbackQuery->id,
                    ]);
                }
            }
        } catch (Exception $e) {
            exceptionReport($e);
        }

        try {
            if ($this->answer ?? $this->softAnswer) {
                wHook()->api()->answerCallbackQuery([
                    'callback_query_id' => wHook()->update()->callbackQuery->id,
                    'text' => $this->answer,
                ]);
            }
        } catch (Exception $e) {
            exceptionReport($e);
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
