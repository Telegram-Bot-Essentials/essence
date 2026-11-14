<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Http\Client\Response;
use TelegramBotEssentials\Essence\Models\MessageOwner;
use Throwable;

/**
 * Records who a group message with inline buttons belongs to, as it is sent:
 * the user whose update (or resumed job) sent it. Every Bot API call goes
 * through LaravelHttpClient, so handlers and companions get this without
 * doing anything. Private chats are skipped: only one user can press there.
 */
class GroupMessageOwners
{
    /** @param  array<mixed>  $options */
    public static function capture(string $url, array $options, Response $response): void
    {
        try {
            if (! str_contains(basename(parse_url($url, PHP_URL_PATH) ?: ''), 'send') && ! str_ends_with($url, '/copyMessage')) {
                return;
            }

            if (! wHook()->check() || ! wHook()->inGroup() || ! self::hasInlineButtons($options)) {
                return;
            }

            $chatId = $response->json('result.chat.id');
            $messageId = $response->json('result.message_id');
            $ownerId = wHook()->user()->getKey();
            if (! is_numeric($chatId) || ! is_numeric($messageId) || ! is_numeric($ownerId)) {
                return;
            }

            MessageOwner::record((int) $chatId, (int) $messageId, (int) $ownerId);
        } catch (Throwable $e) {
            // Never let bookkeeping break a send.
            tbeLog('essence')->warning('Could not record a group message owner: '.$e->getMessage());
        }
    }

    /** @param  array<mixed>  $options */
    private static function hasInlineButtons(array $options): bool
    {
        $formParams = $options['form_params'] ?? null;
        $markup = is_array($formParams) ? ($formParams['reply_markup'] ?? null) : null;

        $multipart = $options['multipart'] ?? null;
        foreach (is_array($multipart) ? $multipart : [] as $part) {
            if (is_array($part) && ($part['name'] ?? null) === 'reply_markup') {
                $markup = $part['contents'] ?? null;
            }
        }

        return is_string($markup) && str_contains($markup, '"inline_keyboard"') && ! str_contains($markup, '"inline_keyboard":[]');
    }
}
