<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\Services\CurrencyFather;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Services\ExceptionHandler;
use TelegramBotEssentials\Essence\Services\StateDataService;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQuery;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQueryBus;
use TelegramBotEssentials\Essence\Telegram\Features\Member\InlineConfirmationFeature;
use TelegramBotEssentials\Essence\Telegram\HttpClients\LaravelHttpClient;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;

if (!function_exists('wHook')) {
    function wHook(): Webhook
    {
        return app(Webhook::class);
    }
}

if (!function_exists('telegramApi')) {
    function telegramApi(string $token, ?string $baseBotUrl = null): Api
    {
        return new Api(
            token: $token,
            httpClientHandler: app(LaravelHttpClient::class),
            baseBotUrl: $baseBotUrl ?? config('tbe-essence.base_bot_url'),
        );
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

if (!function_exists('inlineQueryBus')) {
    function inlineQueryBus(): InlineQueryBus
    {
        return app(InlineQueryBus::class);
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
    function encodeCallback(string $type, string $method, array $params = []): string
    {
        $safeParams = array_filter($params, fn($p) => is_scalar($p) && !is_null($p) && $p !== '');

        $result = empty($safeParams)
            ? $type . '#' . $method
            : $type . '#' . $method . '?' . implode('&', array_map('strval', $safeParams));

        if (strlen($result) > 64) {
            return 'LONG_CALLBACK_DATA'; // TODO: handle this case
        }
        return $result;
    }
}

if (!function_exists('decodeCallback')) {
    function decodeCallback(string $input): array
    {
        $type = null;
        $method = null;
        $params = [];

        if (str_contains($input, '#')) {
            [$typePart, $rest] = explode('#', $input, 2);
            $type = $typePart;

            if (str_contains($rest, '?')) {
                [$methodPart, $raw] = explode('?', $rest, 2);
                $method = $methodPart;
                $params = $raw !== '' ? explode('&', $raw) : [];
            } else {
                $method = $rest;
            }
        } else {
            if (str_contains($input, '?')) {
                [$type, $raw] = explode('?', $input, 2);
                $params = $raw !== '' ? explode('&', $raw) : [];
            } else {
                $type = $input;
            }
        }

        return [
            'type' => $type,
            'method' => $method,
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

if (!function_exists('smartInlineKeysSorter')) {
    function smartInlineKeysSorter(array $array, ?int $step = null, int $limit = 40): array
    {
        collect($array)->each(function ($data) {
            if (!($data instanceof Button)) {
                throw new InvalidArgumentException('Items must be instance of Button');
            }
        });

        $charLen = function (string $s): int {
            if ($s === '') {
                return 0;
            }

            $pattern = '/\X/u';
            if (defined('SYMFONY_GRAPHEME_CLUSTER_RX')) {
                $pattern = '/' . SYMFONY_GRAPHEME_CLUSTER_RX . '/u';
            }

            $length = preg_match_all($pattern, $s, $matches);
            if ($length !== false) {
                return $length;
            }

            if (function_exists('grapheme_strlen')) {
                $length = grapheme_strlen($s);
                if (is_int($length)) {
                    return $length;
                }
            }

            if (function_exists('mb_strlen')) {
                return mb_strlen($s, 'UTF-8');
            }

            if (function_exists('iconv_strlen')) {
                $length = iconv_strlen($s, 'UTF-8');
                if ($length !== false) {
                    return $length;
                }
            }

            $split = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
            if ($split !== false) {
                return count($split);
            }

            return strlen($s);
        };

        if (empty($step)) {
            if (count($array) < 6) $step = 1;
            else $step = 2;
        }

        $list = [];
        $num = 0;
        $scope = 0;
        foreach ($array as $data) {
            $keyText = $data['text'] ?? '';
            $keyLen = $charLen($keyText);

            if (($scope + $keyLen) > $limit) {
                if ($num % $step != 0) $num = ($num + $step) - ($num % $step);
                $scope = 0;
            }
            $row = floor($num / $step);
            $list[$row][] = $data;
            $num++;
            $scope += $keyLen;
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

if (!function_exists('addInlineKeysSmartSorted')) {
    /**
     * @param Keyboard $keyboard
     * @param array $keys
     * @param int|null $step
     * @return void
     */
    function addInlineKeysSmartSorted(Keyboard $keyboard, array $keys, ?int $step = null, int $limit = 30): void
    {
        $rows = smartInlineKeysSorter($keys, $step, $limit);
        foreach ($rows as $row) {
            $keyboard->row($row);
        }
    }
}

if (!function_exists('getInputInlineKeyText')) {
    function getInputInlineKeyText(): ?string
    {
        $callbackQuery = wHook()->update()->callbackQuery;

        if (!$callbackQuery?->data) {
            return null;
        }

        $inlineKeys = $callbackQuery->message?->replyMarkup['inline_keyboard'] ?? null;

        if (!is_iterable($inlineKeys)) {
            return null;
        }

        foreach ($inlineKeys as $rows) {
            if (!is_iterable($rows)) {
                continue;
            }

            foreach ($rows as $key) {
                if (!is_array($key) && !($key instanceof \ArrayAccess)) {
                    continue;
                }

                if (($key['callback_data'] ?? null) === $callbackQuery->data) {
                    return $key['text'] ?? null;
                }
            }
        }

        return null;
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
        return ((wHook()->user()->power >= $power ?? Roles::ADMIN->value)) ||
            (wHook()->bot()->botOwner->id == wHook()->user()->telegramUser->id) ||
            (wHook()->user()->telegramUser->peer_id == config('tbe-essence.developer.peer_id'));
    }
}

if (!function_exists('getResourceName')) {
    function getResourceName(string $resource): string
    {
        $parts = explode('\\', $resource);
        return end($parts);
    }
}

if (!function_exists('exceptionReport')) {
    function exceptionReport(Throwable $e): void
    {
        try {
            $time = time();
            debugMessage(get_class($e));
            if ($e->getMessage()) {
                debugMessage($e->getMessage());
            }
            if (wHook()->requestState()) {
                debugMessage(wHook()->requestState());
            }
            wHook()->api()->sendDocument([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'document' => InputFile::createFromContents($e->getTraceAsString(), $time . '.trace'),
            ]);
            wHook()->api()->sendDocument([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'document' => InputFile::createFromContents(json_encode(wHook()->update(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $time . '.update'),
            ]);
        } catch (Throwable $err) {
            Log::error('Failed to report exception due: ' . $err->getMessage());
        }
        Log::error(get_class($e));
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
    }
}

if (!function_exists('debugMessage')) {
    function debugMessage(string $message): void
    {
        error_log('[TBE] ' . $message);
        try { Log::info($message); } catch (Throwable) {}
        try {
            wHook()->api()->sendMessage([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'text' => $message,
            ]);
        } catch (Throwable) {}
    }
}

if (!function_exists('mixedDebugMessage')) {
    function mixedDebugMessage(mixed $data): void
    {
        $text = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        error_log('[TBE] ' . $text);
        try { Log::info($text); } catch (Throwable) {}
        try {
            wHook()->api()->sendMessage([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'text' => $text,
            ]);
        } catch (Throwable) {}
    }
}

if (!function_exists('exceptionHandler')) {
    function exceptionHandler(): ExceptionHandler
    {
        return app(ExceptionHandler::class);
    }
}

if (!function_exists('inlineConfirmationKey')) {
    function inlineConfirmationKey(string $keyText, string $targetCallbackData, string $backCallbackData, ?string $confirmationText): Button|array|string
    {
        $inlineConfirmation = InlineConfirmation::create([
            'confirmation_text' => $confirmationText,
            'callback_data' => $targetCallbackData,
            'back_callback_data' => $backCallbackData
        ]);

        return Keyboard::inlineButton([
            'text' => $keyText,
            'callback_data' => encodeCallback(InlineConfirmationFeature::$type, 'load', [$inlineConfirmation->id])
        ]);
    }
}

if (!function_exists('stateData')) {
    function stateData(): StateDataService
    {
        return app(StateDataService::class);
    }
}

if (!function_exists('loadStateAnswers')) {
    function loadStateAnswers(string $path): void
    {
        if (!$path) return;
        $namespace = resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, StateAnswer::class)) {
                stateAnswerBus()->addStateAnswer($fqcn);
            }
        }
    }
}

if (!function_exists('loadCallbackQueries')) {
    function loadCallbackQueries(string $path): void
    {
        if (!$path) return;
        $namespace = resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, CallbackQuery::class)) {
                callbackQueryBus()->addCallbackQuery($fqcn);
            }
        }
    }
}

if (!function_exists('loadInlineQueries')) {
    function loadInlineQueries(string $path): void
    {
        if (!$path) return;
        $namespace = resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, InlineQuery::class)) {
                inlineQueryBus()->setHandler($fqcn);
                return;
            }
        }
    }
}

if (!function_exists('addUserReplyKeys')) {
    function addUserReplyKeys(array $replyKeys): void
    {
        foreach ($replyKeys as $replyKeyRow) {
            foreach ($replyKeyRow as $replyKey) {
                if (!is_subclass_of($replyKey, ReplyKey::class))
                    throw new LogicException("ReplyKey {$replyKey} is not a subclass of TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey");
                replyKeyBus()->addReplyKey($replyKey);
            }
        }
    }
}

if (!function_exists('loadReplyKeys')) {
    function loadReplyKeys(string $path): void
    {
        if (!$path) return;
        $namespace = resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, ReplyKey::class)) {
                replyKeyBus()->addReplyKey($fqcn);
            }
        }
    }
}

if (!function_exists('resolveNamespace')) {
    function resolveNamespace(string $path): string
    {
        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__);
            $baseNamespace = 'TelegramBotEssentials\\Essence';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return trim(rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace, '\\');
    }
}
