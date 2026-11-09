<?php

namespace TelegramBotEssentials\Essence\Web\Telegram;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Web\LinkCodes;
use TelegramBotEssentials\Essence\Web\TelegramLink;

/** /start tbe-link-<code>: asks the Telegram account opening it to confirm the link. */
class AskToLinkAccount
{
    public function handle(BotDeepLinkReceived $event): void
    {
        if (! str_starts_with($event->payload, LinkCodes::PAYLOAD_PREFIX)) {
            return;
        }

        $code = substr($event->payload, strlen(LinkCodes::PAYLOAD_PREFIX));
        $account = app(LinkCodes::class)->peek($code);
        $peerId = wHook()->user()->peerId();

        if ($account === null) {
            $this->send((string) __('tbe-essence::web.link_expired'));

            return;
        }

        if (in_array($peerId, $account->linkedPeerIds(), true)) {
            app(LinkCodes::class)->consume($code);
            $this->send((string) __('tbe-essence::web.link_already'));

            return;
        }

        // Said before the question: confirming could not work.
        if (TelegramLink::accountFor($peerId) !== null) {
            $this->send((string) __('tbe-essence::web.link_taken'));

            return;
        }

        $this->send(
            (string) __('tbe-essence::web.link_question', ['account' => LinkCodes::accountName($account)]),
            Keyboard::make()->inline()->row([
                Keyboard::inlineButton(['text' => (string) __('tbe-essence::web.link_confirm'), 'callback_data' => LinkAccountQuery::confirmData($code)]),
                Keyboard::inlineButton(['text' => (string) __('tbe-essence::web.link_cancel'), 'callback_data' => LinkAccountQuery::cancelData($code)]),
            ]),
        );
    }

    /** @param  Keyboard<array-key, mixed>|null  $keyboard */
    private function send(string $text, ?Keyboard $keyboard = null): void
    {
        wHook()->api()->sendMessage(array_filter([
            ...wHook()->replyTo(),
            'text' => $text,
            'reply_markup' => $keyboard,
        ], fn ($value) => $value !== null));
    }
}
