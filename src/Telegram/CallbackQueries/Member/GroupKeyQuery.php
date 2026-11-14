<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member;

use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery as CallbackQueryObject;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Http\Controllers\TelegramWebhookController;
use TelegramBotEssentials\Essence\Support\DefaultKeyboardResolver;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

/**
 * A press on a flow's key in a group, where flow keys are inline buttons
 * rather than a reply keyboard (see DefaultKeyboardResolver). The press is
 * checked against the presser's own conversation in this chat, then replayed
 * as a message carrying the key's text, exactly as if they had typed it, so
 * state answers and forms need no group-specific code.
 *
 * Someone else's flow, or a step that has moved on, has no matching state in
 * the presser's conversation and gets an alert instead.
 */
class GroupKeyQuery extends CallbackQuery
{
    public const TYPE = 'TBE_KEY';

    protected string $type = self::TYPE;

    protected ChatScope $chatScope = ChatScope::Groups;

    public static function callbackData(int $index, string $state): string
    {
        return encodeCallback(self::TYPE, 'press', [$index, self::hash($state)]);
    }

    /** Ties a button to the exact step it was offered on. */
    public static function hash(string $state): string
    {
        return substr(md5($state), 0, 10);
    }

    /**
     * @throws TelegramSDKException
     */
    public function press(int $index, string $hash): void
    {
        $callbackQuery = wHook()->update()->callbackQuery;
        $prompt = $callbackQuery?->message;
        if ($callbackQuery === null || $prompt === null) {
            return;
        }

        $state = wHook()->requestState();
        $buttons = DefaultKeyboardResolver::groupButtons(wHook()->conversation());

        if (! is_string($state) || ! hash_equals(self::hash($state), $hash) || ! isset($buttons[$index])) {
            $this->alert($callbackQuery, __('tbe::general.alerts.notYourButton'));

            return;
        }

        $this->answer();
        $this->takeButtonsOff($prompt);
        $this->replay($callbackQuery, $prompt, $buttons[$index]);
    }

    private function alert(CallbackQueryObject $callbackQuery, string $text): void
    {
        try {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => $text,
                'show_alert' => true,
            ]);
        } catch (TelegramSDKException) {
        }
    }

    /** A used step's buttons go, so they cannot be pressed twice. */
    private function takeButtonsOff(Message $prompt): void
    {
        try {
            wHook()->api()->editMessageReplyMarkup([
                'chat_id' => $prompt->chat->id,
                'message_id' => $prompt->messageId,
                'reply_markup' => json_encode(['inline_keyboard' => []]),
            ]);
        } catch (TelegramSDKException) {
        }
    }

    private function replay(CallbackQueryObject $callbackQuery, Message $prompt, string $text): void
    {
        wHook()->setUpdate(new Update([
            'update_id' => wHook()->update()->updateId,
            'message' => array_filter([
                'message_id' => $prompt->messageId,
                'date' => time(),
                'chat' => $prompt->chat->toArray(),
                'from' => $callbackQuery->from->toArray(),
                'text' => $text,
                'is_topic_message' => $prompt->get('is_topic_message'),
                'message_thread_id' => $prompt->get('message_thread_id'),
            ], fn ($value) => $value !== null),
        ]));

        app(TelegramWebhookController::class)->routeMessage();
    }
}
