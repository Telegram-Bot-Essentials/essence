<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Contracts\DeclaresAbility;
use TelegramBotEssentials\Essence\Contracts\DeclaresChatScope;
use TelegramBotEssentials\Essence\Contracts\ResolvesKeyboard;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Events\BotEventBus;
use TelegramBotEssentials\Essence\Exceptions\CallbackDataTooLong;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\InvalidCallbackParam;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\FormEngine;
use TelegramBotEssentials\Essence\Forms\FormRegistry;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Services\ApiResponse;
use TelegramBotEssentials\Essence\Services\BotUserStatus;
use TelegramBotEssentials\Essence\Services\ExceptionHandler;
use TelegramBotEssentials\Essence\Services\IsolatedTasks;
use TelegramBotEssentials\Essence\Services\NavState;
use TelegramBotEssentials\Essence\Services\OwnerAlerts;
use TelegramBotEssentials\Essence\Services\StateDataService;
use TelegramBotEssentials\Essence\Support\AbilityCatalog;
use TelegramBotEssentials\Essence\Support\HandlerGates;
use TelegramBotEssentials\Essence\Support\TbeLogger;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQueryBus;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\Commands\CommandBus;
use TelegramBotEssentials\Essence\Telegram\Features\Member\InlineConfirmationFeature;
use TelegramBotEssentials\Essence\Telegram\HttpClients\LaravelHttpClient;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQuery;
use TelegramBotEssentials\Essence\Telegram\InlineQueries\InlineQueryBus;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyBus;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswerBus;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Essence\Telegram\TextMatchers\TextMatcher;
use TelegramBotEssentials\Essence\Telegram\TextMatchers\TextMatcherBus;

if (! function_exists('wHook')) {
    function wHook(): Webhook
    {
        return app(Webhook::class);
    }
}

if (! function_exists('tbeLog')) {
    function tbeLog(?string $package = null): TbeLogger
    {
        return new TbeLogger($package);
    }
}

if (! function_exists('ownerAlert')) {
    /**
     * Tell the bot's owner about something only they can fix.
     *
     * @see OwnerAlerts::send()
     */
    function ownerAlert(string $key, Closure|string $text, ?Bot $bot = null, ?int $throttle = null): bool
    {
        return app(OwnerAlerts::class)->send($key, $text, $bot, $throttle);
    }
}

if (! function_exists('isolated')) {
    /**
     * Run $work now, as its own action: a failure is logged and reported, goes
     * no further, and tells nobody. Whether it worked is returned, and $onFail
     * is where to act on it. Do not ignore the result of a step that matters.
     *
     * @param  Closure(Throwable): mixed|null  $onFail
     */
    function isolated(string $name, Closure $work, ?BotUser $for = null, ?Closure $onFail = null): bool
    {
        return app(IsolatedTasks::class)->run($name, $work, $for, $onFail);
    }
}

if (! function_exists('background')) {
    /**
     * Run $work later in a queue worker, as its own action, once any open
     * transaction has committed. It needs a static closure: captured models are
     * reloaded, unsaved changes are not carried over, and there is no live
     * update to answer. Keep it apart from other closures: it is stored as
     * source, and two closures opening on one line cannot be told apart. It
     * runs once unless given $tries: only retry what is safe to repeat.
     *
     * @param  list<int>  $backoff  seconds between attempts
     */
    function background(string $name, Closure $work, ?BotUser $for = null, int $tries = 1, array $backoff = []): void
    {
        app(IsolatedTasks::class)->dispatch($name, $work, $for, $tries, $backoff);
    }
}

if (! function_exists('notifyUser')) {
    /**
     * Tell $user something in their private chat, when that is not what the
     * flow depends on: this never throws, and keeps track of who blocked the
     * bot. Whether it arrived is returned.
     *
     * @param  array<string, mixed>  $params  extra sendMessage parameters
     */
    function notifyUser(BotUser $user, string $text, array $params = []): bool
    {
        return isolated('notify user', function () use ($user, $text, $params) {
            try {
                wHook()->api()->sendMessage([
                    'chat_id' => $user->getAttribute('telegram_user_peer_id'),
                    'text' => $text,
                    'reply_markup' => replyKeyboard(),
                    ...$params,
                ]);
            } catch (Throwable $e) {
                botUserStatus()->reportFailure($user, $e);

                throw $e;
            }

            botUserStatus()->reportSuccess($user);
        }, for: $user);
    }
}

if (! function_exists('tbeApiResponse')) {
    function tbeApiResponse(): ApiResponse
    {
        return app(ApiResponse::class);
    }
}

if (! function_exists('telegramApi')) {
    function telegramApi(string $token, ?string $baseBotUrl = null): Api
    {
        return new Api(
            token: $token,
            httpClientHandler: app(LaravelHttpClient::class),
            baseBotUrl: $baseBotUrl ?? config('tbe-essence.base_bot_url'),
        );
    }
}

if (! function_exists('replyKeyBus')) {
    function replyKeyBus(): ReplyKeyBus
    {
        return app(ReplyKeyBus::class);
    }
}

if (! function_exists('textMatcherBus')) {
    function textMatcherBus(): TextMatcherBus
    {
        return app(TextMatcherBus::class);
    }
}

if (! function_exists('handlerGates')) {
    function handlerGates(): HandlerGates
    {
        return app(HandlerGates::class);
    }
}

if (! function_exists('callbackQueryBus')) {
    function callbackQueryBus(): CallbackQueryBus
    {
        return app(CallbackQueryBus::class);
    }
}

if (! function_exists('stateAnswerBus')) {
    function stateAnswerBus(): StateAnswerBus
    {
        return app(StateAnswerBus::class);
    }
}

if (! function_exists('botEventBus')) {
    function botEventBus(): BotEventBus
    {
        return app(BotEventBus::class);
    }
}

if (! function_exists('inlineQueryBus')) {
    function inlineQueryBus(): InlineQueryBus
    {
        return app(InlineQueryBus::class);
    }
}

if (! function_exists('commandBus')) {
    function commandBus(): CommandBus
    {
        return app(CommandBus::class);
    }
}

if (! function_exists('encodeAnswerState')) {
    /**
     * Encode a user-state payload as `{"t":type,"m":method,"p":params}`.
     *
     * JSON rather than a query string so params keep their types (ints stay
     * ints, nulls survive) and can nest, and so non-ASCII text isn't
     * percent-encoded to three times its size. Invalid UTF-8 is substituted
     * rather than thrown on: user-typed text ends up in here.
     */
    function encodeAnswerState($type, $method, $params = []): string
    {
        $payload = ['t' => $type, 'm' => $method];

        if ($params !== []) {
            $payload['p'] = $params;
        }

        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    }
}

if (! function_exists('decodeAnswerState')) {
    /**
     * The inverse of encodeAnswerState(). Null, empty or undecodable input
     * (a state left behind by another format) yields a null type, which the
     * state-answer bus treats as an unregistered state and resets.
     *
     * @return array{type: ?string, method: ?string, params: array}
     */
    function decodeAnswerState($input): array
    {
        $payload = is_string($input) && $input !== '' ? json_decode($input, true) : null;

        if (! is_array($payload)) {
            return ['type' => null, 'method' => null, 'params' => []];
        }

        $type = $payload['t'] ?? null;
        $method = $payload['m'] ?? null;
        $params = $payload['p'] ?? null;

        return [
            'type' => is_string($type) ? $type : null,
            'method' => is_string($method) ? $method : null,
            'params' => is_array($params) ? $params : [],
        ];
    }
}

if (! function_exists('answerStateSummary')) {
    /**
     * A short `type#method` label for a stored state, for logs: the full
     * payload can carry a whole draft and doesn't belong on every log line.
     */
    function answerStateSummary(?string $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $decoded = decodeAnswerState($state);

        return $decoded['type'] === null ? 'undecodable' : $decoded['type'].'#'.($decoded['method'] ?? '');
    }
}

if (! function_exists('encodeCallback')) {
    /**
     * Encode a callback_data payload for an inline button.
     *
     * Params are positional: every param produces one slot, so a null or
     * empty value encodes as an empty slot rather than vanishing and
     * shifting its successors left. Param keys are not encoded.
     *
     * @throws InvalidCallbackParam when a param cannot be stringified
     * @throws CallbackDataTooLong when the result exceeds Telegram's 64 bytes
     */
    function encodeCallback(string $type, string $method, array $params = []): string
    {
        $encoded = [];

        foreach ($params as $key => $param) {
            if ($param !== null && ! is_scalar($param) && ! $param instanceof Stringable) {
                throw new InvalidCallbackParam(sprintf(
                    'Callback param "%s" for %s#%s is a %s, which cannot be encoded.',
                    $key,
                    $type,
                    $method,
                    get_debug_type($param)
                ));
            }

            $encoded[] = (string) $param;
        }

        $result = $encoded === []
            ? $type.'#'.$method
            : $type.'#'.$method.'?'.implode('&', $encoded);

        if (strlen($result) > 64) {
            throw new CallbackDataTooLong(sprintf(
                'Callback data for %s#%s is %d bytes, over Telegram\'s 64-byte limit. '
                .'Store the payload with stateData() and pass its id instead.',
                $type,
                $method,
                strlen($result)
            ));
        }

        return $result;
    }
}

if (! function_exists('decodeCallback')) {
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

if (! function_exists('nextInArray')) {
    /**
     * The element after $current in $values, wrapping past the end back to the
     * first. Null when $values is empty or $current is not in it; the first
     * element when $current is null.
     *
     * @param  array<array-key, mixed>  $values
     */
    function nextInArray(array $values, mixed $current): mixed
    {
        $values = array_values($values);

        if ($current === null) {
            return $values[0] ?? null;
        }

        $index = array_search($current, $values);
        if ($index === false) {
            return null;
        }

        return $values[($index + 1) % count($values)];
    }
}

if (! function_exists('prevInArray')) {
    /**
     * The element before $current in $values, wrapping past the start back to
     * the last. Null when $values is empty or $current is not in it; the last
     * element when $current is null.
     *
     * @param  array<array-key, mixed>  $values
     */
    function prevInArray(array $values, mixed $current): mixed
    {
        $values = array_values($values);

        if ($current === null) {
            return $values === [] ? null : $values[count($values) - 1];
        }

        $index = array_search($current, $values);
        if ($index === false) {
            return null;
        }

        return $values[($index - 1 + count($values)) % count($values)];
    }
}

if (! function_exists('inlineSorter')) {
    function inlineSorter(array $array, ?int $step = null): array
    {
        if (empty($step)) {
            if (count($array) < 6) {
                $step = 1;
            } else {
                $step = 2;
            }
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

if (! function_exists('smartInlineKeysSorter')) {
    function smartInlineKeysSorter(array $array, ?int $step = null, int $limit = 40): array
    {
        collect($array)->each(function ($data) {
            if (! ($data instanceof Button)) {
                throw new InvalidArgumentException('Items must be instance of Button');
            }
        });

        $charLen = function (string $s): int {
            if ($s === '') {
                return 0;
            }

            $pattern = '/\X/u';
            if (defined('SYMFONY_GRAPHEME_CLUSTER_RX')) {
                $pattern = '/'.SYMFONY_GRAPHEME_CLUSTER_RX.'/u';
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
            if (count($array) < 6) {
                $step = 1;
            } else {
                $step = 2;
            }
        }

        $list = [];
        $num = 0;
        $scope = 0;
        foreach ($array as $data) {
            $keyText = $data['text'] ?? '';
            $keyLen = $charLen($keyText);

            if (($scope + $keyLen) > $limit) {
                if ($num % $step != 0) {
                    $num = ($num + $step) - ($num % $step);
                }
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

if (! function_exists('addInlineKeysSorted')) {
    function addInlineKeysSorted(Keyboard $keyboard, array $keys, ?int $step = null): void
    {
        $rows = inlineSorter($keys, $step);
        foreach ($rows as $row) {
            $keyboard->row($row);
        }
    }
}

if (! function_exists('addInlineKeysSmartSorted')) {
    function addInlineKeysSmartSorted(Keyboard $keyboard, array $keys, ?int $step = null, int $limit = 30): void
    {
        $rows = smartInlineKeysSorter($keys, $step, $limit);
        foreach ($rows as $row) {
            $keyboard->row($row);
        }
    }
}

if (! function_exists('getInputInlineKeyText')) {
    function getInputInlineKeyText(): ?string
    {
        $callbackQuery = wHook()->update()->callbackQuery;

        if (! $callbackQuery?->data) {
            return null;
        }

        $inlineKeys = $callbackQuery->message?->replyMarkup['inline_keyboard'] ?? null;

        if (! is_iterable($inlineKeys)) {
            return null;
        }

        foreach ($inlineKeys as $rows) {
            if (! is_iterable($rows)) {
                continue;
            }

            foreach ($rows as $key) {
                if (! is_array($key) && ! ($key instanceof ArrayAccess)) {
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

if (! function_exists('dependsOn')) {
    /**
     * @throws FeatureIsDisabled
     */
    function dependsOn(?bool $condition, string|TelegramResponse|null $message = null): void
    {
        if (! $condition) {
            throw new FeatureIsDisabled($message);
        }
    }
}

if (! function_exists('replyKeyboard')) {
    /**
     * The reply markup to send with a message: for the webhook's
     * conversation, or for $for - a conversation, or a user, meaning their
     * private chat. Built by whichever ResolvesKeyboard is bound.
     *
     * @return Keyboard<array-key, mixed>
     */
    function replyKeyboard(BotUser|Conversation|null $for = null): Keyboard
    {
        $conversation = match (true) {
            $for instanceof Conversation => $for,
            $for instanceof BotUser => $for->privateConversation(),
            default => wHook()->conversation(),
        };

        return app(ResolvesKeyboard::class)->for($conversation);
    }
}

if (! function_exists('hasAccess')) {
    /**
     * Whether the webhook's user holds $ability on the app's Gate; a null
     * ability is open to everyone.
     */
    function hasAccess(?string $ability): bool
    {
        return $ability === null || Gate::forUser(wHook()->user())->allows($ability);
    }
}

if (! function_exists('abilities')) {
    /** Every Gate ability the bot's handlers ask for, with labels. */
    function abilities(): AbilityCatalog
    {
        return app(AbilityCatalog::class);
    }
}

if (! function_exists('handlerAbility')) {
    /** The Gate ability a handler asks for, or null when anyone may use it. */
    function handlerAbility(object $handler): ?string
    {
        return $handler instanceof DeclaresAbility ? $handler->getAbility() : null;
    }
}

if (! function_exists('privateChatLink')) {
    /**
     * A link that opens the bot's private chat, sending /start with $payload
     * when given (listen for BotDeepLinkReceived). Null when the bot's username
     * cannot be found.
     */
    function privateChatLink(?string $payload = null): ?string
    {
        $username = wHook()->bot()->username();

        if ($username === null) {
            return null;
        }

        return 'https://t.me/'.$username.($payload === null ? '' : '?start='.$payload);
    }
}

if (! function_exists('handlerChatScope')) {
    /** The chats a handler runs in; private only unless it says otherwise. */
    function handlerChatScope(object $handler): ChatScope
    {
        return $handler instanceof DeclaresChatScope ? $handler->getChatScope() : ChatScope::Private;
    }
}

if (! function_exists('canUseHandler')) {
    /**
     * Whether $user (the webhook's user by default) may use $handler. For the
     * webhook's own user, the handler must also run in the chat the update
     * came from (see ChatScope).
     */
    function canUseHandler(object $handler, ?BotUser $user = null): bool
    {
        if ($user === null && wHook()->hasConversation() && ! handlerChatScope($handler)->allows(wHook()->conversation())) {
            return false;
        }

        $ability = handlerAbility($handler);

        // Public handlers never reach the Gate, so they cost nothing and
        // leave no trace in Telescope's gate watcher.
        return $ability === null || Gate::forUser($user ?? wHook()->user())->allows($ability);
    }
}

if (! function_exists('isPublicHandler')) {
    /** Whether $handler is open to everyone, independent of any user. */
    function isPublicHandler(object $handler): bool
    {
        return handlerAbility($handler) === null;
    }
}

if (! function_exists('getResourceName')) {
    function getResourceName(string $resource): string
    {
        $parts = explode('\\', $resource);

        return end($parts);
    }
}

if (! function_exists('exceptionReport')) {
    function exceptionReport(Throwable $e): void
    {
        tbeLog('essence')->error(
            get_class($e).': '.($e->getMessage() ?: 'error message is not provided'),
            ['exception' => $e],
        );

        $chatId = config('tbe-essence.bug_report.telegram_chat_id');
        if (! $chatId || ! config('tbe-essence.logging.telegram_notify')) {
            return;
        }

        try {
            $time = time();
            $summary = get_class($e);
            if ($e->getMessage()) {
                $summary .= "\n".$e->getMessage();
            }
            if (wHook()->requestState()) {
                $summary .= "\nstate: ".wHook()->requestState();
            }
            wHook()->api()->sendMessage([
                'chat_id' => $chatId,
                'text' => $summary,
            ]);
            wHook()->api()->sendDocument([
                'chat_id' => $chatId,
                'document' => InputFile::createFromContents($e->getTraceAsString(), $time.'.trace'),
            ]);
            wHook()->api()->sendDocument([
                'chat_id' => $chatId,
                'document' => InputFile::createFromContents(json_encode(wHook()->update(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $time.'.update'),
            ]);
        } catch (Throwable $err) {
            tbeLog('essence')->warning('Failed to report exception to Telegram: '.$err->getMessage());
        }
    }
}

if (! function_exists('debugMessage')) {
    function debugMessage(string $message): void
    {
        tbeLog('essence')->warning($message);
        if (! config('tbe-essence.logging.telegram_notify')) {
            return;
        }
        try {
            wHook()->api()->sendMessage([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'text' => $message,
            ]);
        } catch (Throwable) {
        }
    }
}

if (! function_exists('mixedDebugMessage')) {
    function mixedDebugMessage(mixed $data): void
    {
        $text = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        tbeLog('essence')->warning($text);
        if (! config('tbe-essence.logging.telegram_notify')) {
            return;
        }
        try {
            wHook()->api()->sendMessage([
                'chat_id' => config('tbe-essence.bug_report.telegram_chat_id'),
                'text' => $text,
            ]);
        } catch (Throwable) {
        }
    }
}

if (! function_exists('exceptionHandler')) {
    function exceptionHandler(): ExceptionHandler
    {
        return app(ExceptionHandler::class);
    }
}

if (! function_exists('inlineConfirmationKey')) {
    function inlineConfirmationKey(string $keyText, string $targetCallbackData, string $backCallbackData, ?string $confirmationText): Button|array|string
    {
        $inlineConfirmation = InlineConfirmation::create([
            'confirmation_text' => $confirmationText,
            'callback_data' => $targetCallbackData,
            'back_callback_data' => $backCallbackData,
        ]);

        return Keyboard::inlineButton([
            'text' => $keyText,
            'callback_data' => encodeCallback(InlineConfirmationFeature::$type, 'load', [$inlineConfirmation->id]),
        ]);
    }
}

if (! function_exists('stateData')) {
    function stateData(): StateDataService
    {
        return app(StateDataService::class);
    }
}

if (! function_exists('navState')) {
    function navState(): NavState
    {
        return app(NavState::class);
    }
}

if (! function_exists('botUserStatus')) {
    function botUserStatus(): BotUserStatus
    {
        return app(BotUserStatus::class);
    }
}

if (! function_exists('formRegistry')) {
    function formRegistry(): FormRegistry
    {
        return app(FormRegistry::class);
    }
}

if (! function_exists('formEngine')) {
    function formEngine(): FormEngine
    {
        return app(FormEngine::class);
    }
}

if (! function_exists('loadForms')) {
    function loadForms(string $path): void
    {
        foreach (handlerClassesIn($path, Form::class) as $fqcn) {
            formRegistry()->addForm($fqcn);
        }
    }
}

if (! function_exists('loadStateAnswers')) {
    function loadStateAnswers(string $path): void
    {
        foreach (handlerClassesIn($path, StateAnswer::class) as $fqcn) {
            stateAnswerBus()->addStateAnswer($fqcn);
        }
    }
}

if (! function_exists('loadCallbackQueries')) {
    function loadCallbackQueries(string $path): void
    {
        foreach (handlerClassesIn($path, CallbackQuery::class) as $fqcn) {
            callbackQueryBus()->addCallbackQuery($fqcn);
        }
    }
}

if (! function_exists('loadCommands')) {
    function loadCommands(string $path): void
    {
        foreach (handlerClassesIn($path, Command::class) as $fqcn) {
            commandBus()->addCommand($fqcn);
        }
    }
}

if (! function_exists('loadInlineQueries')) {
    function loadInlineQueries(string $path): void
    {
        foreach (handlerClassesIn($path, InlineQuery::class) as $fqcn) {
            inlineQueryBus()->setHandler($fqcn);

            return;
        }
    }
}

if (! function_exists('addUserReplyKeys')) {
    function addUserReplyKeys(array $replyKeys): void
    {
        foreach ($replyKeys as $replyKeyRow) {
            foreach ($replyKeyRow as $replyKey) {
                if (! is_subclass_of($replyKey, ReplyKey::class)) {
                    throw new LogicException("ReplyKey {$replyKey} is not a subclass of TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey");
                }
                replyKeyBus()->addReplyKey($replyKey);
            }
        }
    }
}

if (! function_exists('loadReplyKeys')) {
    function loadReplyKeys(string $path): void
    {
        foreach (handlerClassesIn($path, ReplyKey::class) as $fqcn) {
            replyKeyBus()->addReplyKey($fqcn);
        }
    }
}

if (! function_exists('loadTextMatchers')) {
    function loadTextMatchers(string $path): void
    {
        foreach (handlerClassesIn($path, TextMatcher::class) as $fqcn) {
            textMatcherBus()->addTextMatcher($fqcn);
        }
    }
}

if (! function_exists('handlerClassesIn')) {
    /**
     * The concrete subclasses of $base under $path, sub-folders included:
     * a handler's folder is the app's own layout, never a rule.
     *
     * @template T of object
     *
     * @param  class-string<T>  $base
     * @return list<class-string<T>>
     */
    function handlerClassesIn(string $path, string $base): array
    {
        if (! $path || ! is_dir($path)) {
            return [];
        }
        $namespace = resolveNamespace($path);

        $classes = [];
        foreach (File::allFiles($path) as $file) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $file->getRelativePath());
            $fqcn = $namespace.'\\'.($relative === '' ? '' : $relative.'\\').$file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, $base) && ! (new ReflectionClass($fqcn))->isAbstract()) {
                $classes[] = $fqcn;
            }
        }

        return $classes;
    }
}

if (! function_exists('resolveNamespace')) {
    function resolveNamespace(string $path): string
    {
        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__);
            $baseNamespace = 'TelegramBotEssentials\\Essence';
        }

        $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return trim(rtrim($baseNamespace, '\\').'\\'.$relativeNamespace, '\\');
    }
}
