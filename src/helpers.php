<?php

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\FeatureIsDisabled;
use Elyar\TelegramBotEssentials\Models\InlineConfirmation;
use Elyar\TelegramBotEssentials\Services\Currency;
use Elyar\TelegramBotEssentials\Services\CurrencyFather;
use Elyar\TelegramBotEssentials\Services\ApiResponse;
use Elyar\TelegramBotEssentials\Services\Billing;
use Elyar\TelegramBotEssentials\Services\ExceptionHandler;
use Elyar\TelegramBotEssentials\Services\Gateways\Gateways;
use Elyar\TelegramBotEssentials\Support\Webhook;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQueryBus;
use Elyar\TelegramBotEssentials\Telegram\Feature\Member\InlineConfirmationFeature;
use Elyar\TelegramBotEssentials\Telegram\ReplyKeys\ReplyKeyBus;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswerBus;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

if (!function_exists('wHook')) {
    function wHook(): Webhook
    {
        return app(Webhook::class);
    }
}

if (!function_exists('apiResponse')) {
    function apiResponse(): ApiResponse
    {
        return app(ApiResponse::class);
    }
}

if (!function_exists('currency')) {
    function currency(): Currency
    {
        return app(Currency::class);
    }
}

if (!function_exists('billing')) {
    function billing(): Billing
    {
        return app(Billing::class);
    }
}

if (!function_exists('replyKeyBus')) {
    function replyKeyBus(): ReplyKeyBus
    {
        return app(ReplyKeyBus::class);
    }
}

if (!function_exists('callbackQueryBus')) {
    function callbackQueryBus(): CallbackQueryBus
    {
        return app(CallbackQueryBus::class);
    }
}

if (!function_exists('stateAnswerBus')) {
    function stateAnswerBus(): StateAnswerBus
    {
        return app(StateAnswerBus::class);
    }
}

if (!function_exists('gateways')) {
    function gateways(): Gateways
    {
        return app(Gateways::class);
    }
}

if (!function_exists('encodeAnswerState')) {
    function encodeAnswerState($type, $method, $params = []): string
    {
        $queryString = http_build_query($params);
        return $type . '#' . $method . ($queryString ? '?' . $queryString : '');
    }
}

if (!function_exists('decodeAnswerState')) {
    function decodeAnswerState($input): array
    {
        $parts = explode('#', $input);
        $type = $parts[0] ?? null;

        $methodAndParams = explode('?', $parts[1] ?? '');
        $method = $methodAndParams[0] ?? null;
        parse_str($methodAndParams[1] ?? '', $params);

        return [
            'type' => $type,
            'method' => $method,
            'params' => $params,
        ];
    }
}

if (!function_exists('encodeCallback')) {
    function encodeCallback(string $type, array $params = []): string
    {
        $safeParams = array_filter($params, fn($p) => is_scalar($p) && !is_null($p) && $p !== '');

        return empty($safeParams)
            ? $type
            : $type . '?' . implode('&', array_map('strval', $safeParams));
    }
}

if (!function_exists('decodeCallback')) {
    function decodeCallback(string $input): array
    {
        if (str_contains($input, '?')) {
            [$type, $raw] = explode('?', $input, 2);
            $params = explode('&', $raw);
        } else {
            $type = $input;
            $params = null;
        }

        return [
            'type' => $type,
            'params' => $params,
        ];
    }
}

if (!function_exists('inlineSorter')) {
    function inlineSorter(array $array, ?int $step = null): array
    {
        if (empty($step)) {
            if (count($array) < 6) $step = 1;
            else $step = 2;
        }

        $list = [];
        $num = 0;
        foreach ($array as $data) {
            $row = floor($num / $step);
            $list[$row][] = $data;
            $num++;
        }
        return $list;
    }
}

if (!function_exists('addInlineKeysSorted')) {
    /**
     * @param Keyboard $keyboard
     * @param array $keys
     * @param int|null $step
     * @return void
     */
    function addInlineKeysSorted(Keyboard $keyboard, array $keys, ?int $step = null): void
    {
        $rows = inlineSorter($keys, $step);
        foreach ($rows as $row) {
            $keyboard->row($row);
        }
    }
}

if (!function_exists('getInputInlineKeyText')) {
    function getInputInlineKeyText(): ?string
    {
        $result = null;
        $data = wHook()->update()->callbackQuery?->data;
        $inlineKeys = wHook()->update()->callbackQuery?->message?->replyMarkup['inline_keyboard'];

        foreach ($inlineKeys as $rows) {
            foreach ($rows as $key) {
                if ($key['callback_data'] ?? null === $data) {
                    $result = $key['text'];
                    break;
                }
            }
        }

        return $result;
    }
}

if (!function_exists('dependsOn')) {
    /**
     * @throws FeatureIsDisabled
     */
    function dependsOn(?bool $condition, ?string $message = null): void
    {
        if (!$condition)
            throw new FeatureIsDisabled($message);
    }
}

if (!function_exists('hasAccess')) {

    function hasAccess(?int $power = null): bool
    {
        return (wHook()->user()->power >= $power ?? Roles::ADMIN->value) || (wHook()->bot()->botOwner->id == wHook()->user()->telegramUser->id);
    }
}

if (!function_exists('getNextFromArray')) {
    function getNextFromArray(array $array, $current)
    {
        $index = array_search($current, $array);

        if ($index === false || empty($array)) {
            return null;
        }

        $nextIndex = ($index + 1) % count($array);
        return $array[$nextIndex];
    }
}

if (!function_exists('priceIn')) {
    function priceIn(string $price): CurrencyFather
    {
        return CurrencyFather::from(wHook()->bot()->currency)->amount($price);
    }
}

if(!function_exists('getResourceName')){
    function getResourceName(string $resource): string
    {
        $parts = explode('\\', $resource);
        return end($parts);
    }
}

if(!function_exists('exceptionReport')){
    function exceptionReport(Exception $e): void
    {
        try {
            $time = time();
            if($e->getMessage()){
                wHook()->api()->sendMessage([
                    'chat_id' => config('telegram-bot-essentials.bug_report.telegram_chat_id'),
                    'text' => $e->getMessage(),
                ]);
            }
            if(wHook()->user()->state){
                wHook()->api()->sendMessage([
                    'chat_id' => config('telegram-bot-essentials.bug_report.telegram_chat_id'),
                    'text' => wHook()->user()->state,
                ]);
            }
            wHook()->api()->sendDocument([
                'chat_id' => config('telegram-bot-essentials.bug_report.telegram_chat_id'),
                'document' => InputFile::createFromContents($e->getTraceAsString(), $time.'.trace'),
            ]);
            wHook()->api()->sendDocument([
                'chat_id' => config('telegram-bot-essentials.bug_report.telegram_chat_id'),
                'document' => InputFile::createFromContents(json_encode(wHook()->update(), JSON_PRETTY_PRINT), $time.'.update'),
            ]);
        } catch (TelegramSDKException $e) {

        }
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
    }
}

if(!function_exists('ExceptionHandler')){
    function ExceptionHandler(): ExceptionHandler
    {
        return app(ExceptionHandler::class);
    }
}

if(!function_exists('inlineConfirmationKey')){
    function inlineConfirmationKey(string $keyText, string $targetCallbackData, string $backCallbackData, string $confirmationText = null): Button|array|string
    {
        $inlineConfirmation = InlineConfirmation::create([
            'confirmation_text' => $confirmationText,
            'callback_data' => $targetCallbackData,
            'back_callback_data' => $backCallbackData
        ]);

        return Keyboard::inlineButton([
            'text' => $keyText,
            'callback_data' => encodeCallback(InlineConfirmationFeature::$type, ['load', $inlineConfirmation->id])
        ]);
    }
}
