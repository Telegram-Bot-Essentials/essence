<?php

namespace Elyar\TelegramBotEssentials\Models;

use Elyar\TelegramBotEssentials\Telegram\TelegramResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\TelegramObject;

class MessageMeta extends Model
{
    static $type = 'MESSAGE_META';
    protected $fillable = ['chat_id', 'message_id', 'message_text', 'message_reply_markup'];
    protected $casts = [
        'message_text' => 'string',
        'message_reply_markup' => 'array',
    ];

    public static function makeWithCurrentMessage(): MessageMeta
    {
        $messageMeta = MessageMeta::make();
        $messageMeta->initializeModel();
        return $messageMeta;
    }

    public static function makeWithMessage(Message $message)
    {
        $messageMeta = MessageMeta::make();
        $messageMeta->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
        return $messageMeta;
    }

    public function setMessageReplyMarkupAttribute(TelegramObject|array|string $replyMarkup): void
    {
        $this->attributes['message_reply_markup'] = is_string($replyMarkup) ? $replyMarkup : json_encode($replyMarkup);
    }

    public function getMessageReplyMarkupAttribute($value): Keyboard
    {
        return Keyboard::make(json_decode($value, true));
    }

    public function action(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param string|null $lockMessage
     */
    public function lockAction(?string $lockMessage = null, string $customEmoji = "🔒"): void
    {
        if (
            empty($this->chat_id) ||
            empty($this->message_id) ||
            empty($this->message_text) ||
            empty($this->message_reply_markup)
        ) {
            $this->initializeModel();
        }

        $replyMarkup = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => $customEmoji . " " . ($lockMessage ?? "Locked For Action"),
                    'callback_data' => encodeCallback(self::$type, ['action_is_locked', $this->id]),
                ])
            ]);

        $this->lockingProcess($replyMarkup);
    }

    public function cancelableLockAction(?string $lockMessage = null): void
    {
        $this->initializeModel();

        $replyMarkup = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => "🔒 " . ($lockMessage ?? "Locked For Action"),
                    'callback_data' => encodeCallback(self::$type, ['action_is_locked', $this->id]),
                ])
            ])
            ->row([
                Keyboard::inlineButton([
                    'text' => "🗑️ Cancel",
                    'callback_data' => encodeCallback(self::$type, ['cancel_action', $this->id]),
                ])
            ]);

        $this->lockingProcess($replyMarkup);
    }

    public function initializeModel(?int $chat_id = null, ?int $message_id = null, ?string $message_text = null, ?string $message_reply_markup = null): void
    {
        if (
            wHook()->update()->callbackQuery ||
            (isset($chat_id) && isset($message_id) && isset($message_text) && isset($message_reply_markup))
        ) {
            $this->chat_id = $chat_id ?? wHook()->update()->callbackQuery->message->chat->id;
            $this->message_id = $message_id ?? wHook()->update()->callbackQuery->message->messageId;
            $this->message_text = $message_text ?? wHook()->update()->callbackQuery->message->text;
            $this->message_reply_markup = $message_reply_markup ?? wHook()->update()->callbackQuery->message->replyMarkup;
        }

        $this->save();
    }

    /**
     * @throws TelegramSDKException
     */
    private function lockingProcess(Keyboard $replyMarkup): void
    {
        wHook()->api()->editMessageReplyMarkup([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
            'reply_markup' => $replyMarkup,
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    public function continueAction(): void
    {
        wHook()->api()->deleteMessage([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
        ]);

        $message = wHook()->api()->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $this->message_text,
            'reply_markup' => $this->message_reply_markup,
        ]);

        $this->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
    }

    /**
     * @throws TelegramSDKException
     */
    public function revertAction(): void
    {
        wHook()->api()->editMessageText([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
            'text' => $this->message_text,
            'reply_markup' => $this->message_reply_markup,
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    public function updateAction(TelegramResponse|array $data): void
    {
        if ($data instanceof TelegramResponse) {
            $data->update($this->chat_id, $this->message_id);
        }else{
            wHook()->api()->editMessageText([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
                'text' => $data['text'],
                'reply_markup' => $data['reply_markup'],
                'parse_mode' => $data['parse_mode'] ?? null,
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    public function updateAndContinueAction(TelegramResponse|array $data): void
    {
        wHook()->api()->deleteMessage([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
        ]);

        if ($data instanceof TelegramResponse) {
            $message = $data->send($this->chat_id);
        }else{
            $message = wHook()->api()->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $data['text'],
                'reply_markup' => $data['reply_markup'],
                'parse_mode' => $data['parse_mode'] ?? null,
            ]);
        }

        $this->initializeModel($message->chat->id, $message->messageId, $message->text, $message->replyMarkup);
    }

    public function deleteMessage(): void
    {
        wHook()->api()->deleteMessage([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
        ]);
    }
}
