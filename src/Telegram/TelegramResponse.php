<?php

namespace TelegramBotEssentials\Essence\Telegram;

use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use Exception;
use Throwable;
use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Message;

class TelegramResponse
{
    private ?Model $modelForMessageMeta = null;
    private ?string $messageMetaTag = null;
    private ?string $softAnswer = null;
    private ?array $navStateData = null;

    public function __construct(
        public ?string            $text = null,
        public ?Keyboard          $replyMarkup = null,
        public ?string            $answer = null,
        public ?string            $parseMode = null,
        public string|InputFile|null $photo = null,
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
            photo: $data['photo'] ?? null,
        );
    }

    public function toEditMessageArray(int|string $chatId, int $messageId): array
    {
        if ($this->photo) {
            return array_filter([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'media' => $this->buildInputMedia(),
                'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
            ]);
        }

        return array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $this->text,
            'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
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

    public function photo(string|InputFile $photo): self
    {
        $this->photo = $photo;
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

    public function navState(array $data): self
    {
        $this->navStateData = $data;
        return $this;
    }

    /**
     * @throws TelegramSDKException
     */
    public function send(string|int|null $chatId = null): Message
    {
        $params = array_filter([
            'chat_id' => $chatId ?? wHook()->user()->telegramUser->peer_id,
            'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
            'parse_mode' => $this->parseMode,
        ]);

        $statusTarget = $this->statusTarget($chatId);

        try {
            if ($this->photo) {
                $message = wHook()->api()->sendPhoto(array_merge($params, array_filter([
                    'photo' => $this->photo,
                    'caption' => $this->text,
                ])));
            } else {
                $message = wHook()->api()->sendMessage(array_merge($params, array_filter([
                    'text' => $this->text,
                ])));
            }
        } catch (Throwable $e) {
            $statusTarget && botUserStatus()->reportFailure($statusTarget, $e);

            throw $e;
        }

        $statusTarget && botUserStatus()->reportSuccess($statusTarget);

        $this->saveMessageMeta($message);
        $this->saveNavState($message);

        return $message;
    }

    /**
     * The bot user whose reachability this send tells us about, or null when it
     * tells us nothing: a send aimed at another chat (an admin group, a
     * channel) says nothing about the user currently in context.
     */
    private function statusTarget(string|int|null $chatId): ?BotUser
    {
        if (!wHook()->check()) {
            return null;
        }

        $user = wHook()->user();

        if ($chatId !== null && (int) $chatId !== (int) $user->telegramUser?->peer_id) {
            return null;
        }

        return $user;
    }

    /**
     * @throws TelegramSDKException
     */
    public function update(string|int|null $chatId = null, string|int|null $messageId = null): void
    {
        try {
            if ($this->photo) {
                try {
                    $message = $this->editMessageMedia(
                        $chatId ?? wHook()->update()->callbackQuery->message->chat->id,
                        $messageId ?? wHook()->update()->callbackQuery->message->messageId,
                    );

                    $this->saveMessageMeta($message);
                    $this->saveNavState($message);
                } catch (Exception) {
                    wHook()->api()->answerCallbackQuery([
                        'callback_query_id' => wHook()->update()->callbackQuery->id,
                    ]);
                }
            } elseif ($this->text) {
                try {
                    $message = wHook()->api()->editMessageText(
                        array_filter([
                            'chat_id' => $chatId ?? wHook()->update()->callbackQuery->message->chat->id,
                            'message_id' => $messageId ?? wHook()->update()->callbackQuery->message->messageId,
                            'text' => $this->text,
                            'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
                            'parse_mode' => $this->parseMode,
                        ])
                    );

                    $this->saveMessageMeta($message);
                    $this->saveNavState($message);
                } catch (Exception) {
                    try {
                        $message = wHook()->api()->editMessageCaption(
                            array_filter([
                                'chat_id' => $chatId ?? wHook()->update()->callbackQuery->message->chat->id,
                                'message_id' => $messageId ?? wHook()->update()->callbackQuery->message->messageId,
                                'caption' => $this->text,
                                'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
                                'parse_mode' => $this->parseMode,
                            ])
                        );

                        $this->saveMessageMeta($message);
                        $this->saveNavState($message);
                    } catch (Exception) {
                        wHook()->api()->answerCallbackQuery([
                            'callback_query_id' => wHook()->update()->callbackQuery->id,
                        ]);
                    }
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
            'replyMarkup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
            'answer' => $this->answer,
            'parseMode' => $this->parseMode,
            'photo' => $this->photo,
        ]);
    }

    private function buildInputMedia(): string
    {
        $attachName = 'photo';

        return json_encode(array_filter([
            'type' => 'photo',
            'media' => $this->photo instanceof InputFile
                ? "attach://{$attachName}"
                : $this->photo,
            'caption' => $this->text,
            'parse_mode' => $this->parseMode,
        ]), JSON_THROW_ON_ERROR);
    }

    /**
     * @throws TelegramSDKException
     */
    private function editMessageMedia(int|string $chatId, int $messageId): Message
    {
        $attachName = 'photo';

        $params = array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'media' => $this->buildInputMedia(),
            'reply_markup' => $this->replyMarkup?->isEmpty() ? null : $this->replyMarkup,
        ]);

        if (! $this->photo instanceof InputFile) {
            return wHook()->api()->editMessageMedia($params);
        }

        $params[$attachName] = $this->photo;

        $response = wHook()->api()->post(
            'editMessageMedia',
            $this->toMultipartParams($params),
            true,
        );

        return new Message($response->getDecodedBody());
    }

    private function toMultipartParams(array $params): array
    {
        return collect($params)
            ->reject(static fn ($value): bool => $value === null)
            ->map(function ($contents, $name) {
                if ($contents instanceof InputFile) {
                    return [
                        'name' => $name,
                        'contents' => $contents->getContents(),
                        'filename' => $contents->getFilename(),
                    ];
                }

                if ($name === 'reply_markup') {
                    return ['name' => $name, 'contents' => (string) $contents];
                }

                return ['name' => $name, 'contents' => $contents];
            })
            ->values()
            ->all();
    }

    private function saveMessageMeta(Message $message): void
    {
        if (!$this->modelForMessageMeta) {
            return;
        }

        $messageMeta = MessageMeta::makeWithMessage($message, $this->messageMetaTag);
        $messageMeta->action()->associate($this->modelForMessageMeta);
        $messageMeta->save();
    }

    private function saveNavState(Message $message): void
    {
        if ($this->navStateData === null) {
            return;
        }

        navState()->put($message->chat->id, $message->messageId, $this->navStateData);
    }
}
