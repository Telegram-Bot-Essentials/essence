<?php

namespace TelegramBotEssentials\Essence\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\TelegramObject;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class MessageMeta extends Model
{
    use Prunable;

    public static $type = 'MESSAGE_META';

    protected $fillable = ['chat_id', 'message_id', 'message_text', 'message_reply_markup'];

    protected $casts = [
        'message_text' => 'string',
        'message_reply_markup' => 'array',
    ];

    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subDays(config('tbe-essence.pruning.message_metas_days', 7)));
    }

    public static function makeWithCurrentMessage(?string $tag = null): MessageMeta
    {
        $messageMeta = MessageMeta::make();
        $messageMeta->initializeModel();
        $messageMeta->tag = $tag;
        $messageMeta->save();

        return $messageMeta;
    }

    public function initializeModel(?int $chat_id = null, ?int $message_id = null, ?string $message_text = null, ?string $message_reply_markup = null, ?string $tag = null): void
    {
        if (wHook()->update()->callbackQuery) {
            $this->chat_id = $chat_id ?? wHook()->update()->callbackQuery->message->chat->id;
            $this->message_id = $message_id ?? wHook()->update()->callbackQuery->message->messageId;
            $this->message_text = $message_text ?? wHook()->update()->callbackQuery->message->text;
            $this->message_reply_markup = $message_reply_markup ?? wHook()->update()->callbackQuery->message->replyMarkup;
        } elseif (isset($chat_id) && isset($message_id)) {
            $this->chat_id = $chat_id;
            $this->message_id = $message_id;
            if (isset($message_text)) {
                $this->message_text = $message_text;
            }
            if (isset($message_reply_markup)) {
                $this->message_reply_markup = $message_reply_markup;
            }
        }
        $this->tag = $tag;
        $this->save();
    }

    public static function makeWithMessage(Message $message, ?string $tag = null)
    {
        $messageMeta = MessageMeta::make();
        $messageMeta->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
        $messageMeta->tag = $tag;
        $messageMeta->save();

        return $messageMeta;
    }

    public function setMessageReplyMarkupAttribute(TelegramObject|array|string|null $replyMarkup): void
    {
        $this->attributes['message_reply_markup'] = $replyMarkup === null ? null : (is_string($replyMarkup) ? $replyMarkup : json_encode($replyMarkup));
    }

    public function getMessageReplyMarkupAttribute($value): ?Keyboard
    {
        if ($value === null) {
            return null;
        }

        return Keyboard::make(json_decode($value, true));
    }

    public function action(): MorphTo
    {
        return $this->morphTo();
    }

    private function isIgnorableError(Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'message is not modified') ||
               str_contains($message, 'query is too old and response timeout expired or query ID is invalid');
    }

    public function lockAction(?string $lockMessage = null, string $customEmoji = '🔒'): void
    {
        if (empty($this->chat_id) || empty($this->message_id) || empty($this->message_text)) {
            $this->initializeModel();
        }

        $replyMarkup = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => $customEmoji.' '.($lockMessage ?? 'Locked For Action'),
                    'callback_data' => encodeCallback(self::$type, 'action_is_locked', [$this->id]),
                ]),
            ]);

        $this->lockingProcess($replyMarkup);
    }

    /**
     * @throws TelegramSDKException
     */
    private function lockingProcess(Keyboard $replyMarkup): void
    {
        try {
            wHook()->api()->editMessageReplyMarkup([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
                'reply_markup' => $replyMarkup,
            ]);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }
    }

    public function cancelableLockAction(?string $lockMessage = null): void
    {
        $this->initializeModel();

        $replyMarkup = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => '🔒 '.($lockMessage ?? 'Locked For Action'),
                    'callback_data' => encodeCallback(self::$type, 'action_is_locked', [$this->id]),
                ]),
            ])
            ->row([
                Keyboard::inlineButton([
                    'text' => '🗑️ Cancel',
                    'callback_data' => encodeCallback(self::$type, 'cancel_action', [$this->id]),
                ]),
            ]);

        $this->lockingProcess($replyMarkup);
    }

    /**
     * @throws TelegramSDKException
     */
    public function continueAction(): void
    {
        try {
            wHook()->api()->deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ]);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }

        try {
            $message = wHook()->api()->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $this->message_text,
                'reply_markup' => $this->message_reply_markup,
            ]);
            $this->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }
    }

    public function deleteMessage(): void
    {
        try {
            wHook()->api()->deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ]);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }
    }

    public function revertAction(): void
    {
        if (empty($this->message_text)) {
            return;
        }

        try {
            wHook()->api()->editMessageText([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
                'text' => $this->message_text,
                'reply_markup' => $this->message_reply_markup,
            ]);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }
    }

    /**
     * @throws TelegramSDKException
     */
    public function updateAction(TelegramResponse|array $data): void
    {
        if ($data instanceof TelegramResponse) {
            $data->update($this->chat_id, $this->message_id);
        } else {
            try {
                wHook()->api()->editMessageText([
                    'chat_id' => $this->chat_id,
                    'message_id' => $this->message_id,
                    'text' => $data['text'],
                    'reply_markup' => $data['reply_markup'],
                    'parse_mode' => $data['parse_mode'] ?? null,
                ]);
            } catch (Exception $e) {
                if (! $this->isIgnorableError($e)) {
                    exceptionReport($e);
                }
            }
        }
    }

    /**
     * @throws TelegramSDKException
     */
    public function updateAndContinueAction(TelegramResponse|array $data): void
    {
        try {
            wHook()->api()->deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ]);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }

        try {
            $message = $data instanceof TelegramResponse
                ? $data->send($this->chat_id)
                : wHook()->api()->sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => $data['text'],
                    'reply_markup' => $data['reply_markup'],
                    'parse_mode' => $data['parse_mode'] ?? null,
                ]);
            $this->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
        } catch (Exception $e) {
            if (! $this->isIgnorableError($e)) {
                exceptionReport($e);
            }
        }
    }
}
