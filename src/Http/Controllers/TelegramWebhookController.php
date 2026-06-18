<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Events\BotCallbackQueryHandled;
use TelegramBotEssentials\Essence\Events\BotInlineQueryHandled;
use TelegramBotEssentials\Essence\Events\BotCommandHandled;
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Events\BotReplyKeyHandled;
use TelegramBotEssentials\Essence\Events\BotStateAnswerHandled;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Events\BotUpdateUnhandled;
use TelegramBotEssentials\Essence\Support\WebhookContext;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use Throwable;

class TelegramWebhookController extends Controller
{
    use CanCancelOldProcess;

    public function __invoke(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
//        App::setLocale(wHook()->bot()->settings->language);

        try {
            dependsOn(!wHook()->bot()->suspended, ('tbe::general.alerts.botIsOff'));
            dependsOn(
                is_null(wHook()->bot()->activated_until) || wHook()->bot()->activated_until->isFuture(),
                __('tbe::general.alerts.botIsOff'
                ));
//            if (!hasAccess()) dependsOn(wHook()->bot()->settings->bot_status, __('tbe::general.alerts.botIsOff'));
            $this->initializeOptions();
            $this->processUpdate();
        } catch (Throwable $e) {
            exceptionHandler()->handle($e);
        }
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    private function initializeOptions()
    {
        $commands = config('tbe-essence.commands') ?? [];

        foreach ($commands as $command) {
            if (!is_subclass_of($command, Command::class))
                throw new LogicException("ReplyKey {$command} is not a subclass of namespace Telegram\Bot\Commands\Command");
            wHook()->api()->addCommand($command);
        }

        $adminQueries = base_path('app/Telegram/CallbackQueries/Admin');
        $memberQueries = base_path('app/Telegram/CallbackQueries/Member');
        $adminStateAnswers = base_path('app/Telegram/StateAnswers/Admin');
        $memberStateAnswers = base_path('app/Telegram/StateAnswers/Member');
//        $adminReplyKeys = base_path('app/Telegram/ReplyKeys/Admin');
//        $memberReplyKeys = base_path('app/Telegram/ReplyKeys/Member');
        $inlineQueriesPath = base_path('app/Telegram/InlineQueries');

        if (is_dir($adminQueries)) loadCallbackQueries($adminQueries);
        if (is_dir($memberQueries)) loadCallbackQueries($memberQueries);

        if (is_dir($adminStateAnswers)) loadStateAnswers($adminStateAnswers);
        if (is_dir($memberStateAnswers)) loadStateAnswers($memberStateAnswers);

        if (is_dir($inlineQueriesPath)) loadInlineQueries($inlineQueriesPath);

        foreach (config('tbe-essence.keyboard') ?? [] as $values) {
            addUserReplyKeys($values);
        }

        loadCallbackQueries(realpath(__DIR__ . '/../../Telegram/CallbackQueries/Member'));
        loadCallbackQueries(realpath(__DIR__ . '/../../Telegram/CallbackQueries/Admin'));
        loadStateAnswers(realpath(__DIR__ . '/../../Telegram/StateAnswers/Member'));
        loadStateAnswers(realpath(__DIR__ . '/../../Telegram/StateAnswers/Admin'));
        loadReplyKeys(realpath(__DIR__ . '/../../Telegram/ReplyKeys/Member'));
        loadReplyKeys(realpath(__DIR__ . '/../../Telegram/ReplyKeys/Admin'));
    }

    /**
     * @throws LogicException
     * @throws TbeLogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function processUpdate()
    {
        $context = WebhookContext::capture();

        $update = wHook()->update();
        $updateType = $update->detectType() ?? 'unknown';

        botEventBus()->fire(new BotUpdateReceived($context, $updateType));

        $commandProcessed = false;
        $keyProcessed = false;
        $answerProcessed = false;

        if ($update->message) {
            if (str_starts_with($update->message->text, '/')) {
                [$command, $payload] = array_pad(explode(' ', $update->message->text, 2), 2, null);

                stateAnswerBus()->cancelHandler(wHook()->requestState());
                $this->cancelOldProcess();
                wHook()->api()->processCommand($update);
                $commandProcessed = true;

                botEventBus()->fire(new BotCommandHandled($context, $command));

                if ($payload !== null && strtolower($command) === '/start') {
                    botEventBus()->fire(new BotDeepLinkReceived($context, $payload));
                }
            } else {
                $keyProcessed = replyKeyBus()->processReplyKey();
                if ($keyProcessed) {
                    botEventBus()->fire(new BotReplyKeyHandled($context, $update->message->text));
                } elseif (wHook()->user()->state) {
                    $answerProcessed = stateAnswerBus()->processStateAnswers();
                    if ($answerProcessed) {
                        botEventBus()->fire(new BotStateAnswerHandled($context, wHook()->requestState()));
                    }
                }
            }

            $requestIsInvalid = !($commandProcessed || $keyProcessed || $answerProcessed);
            if ($requestIsInvalid) {
                botEventBus()->fire(new BotUpdateUnhandled($context));

                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => __('tbe::general.alerts.requestIsInvalid'),
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            }
        } elseif ($update->callbackQuery) {
            $callbackData = decodeCallback($update->callbackQuery->data);
            $handled = callbackQueryBus()->processCallbackQueries();
            if ($handled) {
                botEventBus()->fire(new BotCallbackQueryHandled($context, $callbackData['type'], $callbackData['method']));
            }
        } elseif ($update->inlineQuery) {
            $handled = inlineQueryBus()->processInlineQuery();
            if ($handled) {
                botEventBus()->fire(new BotInlineQueryHandled($context, $update->inlineQuery->query ?? ''));
            }
        }
    }
}
