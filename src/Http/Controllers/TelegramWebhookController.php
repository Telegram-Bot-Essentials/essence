<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Events\BotCallbackQueryHandled;
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Events\BotInlineQueryHandled;
use TelegramBotEssentials\Essence\Events\BotReplyKeyHandled;
use TelegramBotEssentials\Essence\Events\BotStateAnswerHandled;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Events\BotUpdateUnhandled;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Http\Middleware\TelegramBotAuthentication;
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
            dependsOn(! wHook()->bot()->suspended, ('tbe::general.alerts.botIsOff'));
            dependsOn(
                is_null(wHook()->bot()->activated_until) || wHook()->bot()->activated_until->isFuture(),
                __('tbe::general.alerts.botIsOff'
                ));
            //            if (!hasAccess()) dependsOn(wHook()->bot()->settings->bot_status, __('tbe::general.alerts.botIsOff'));
            // Blocking or unblocking the bot routes through no handler, so
            // it is answered before processUpdate() consults the buses.
            if ($this->processChatMemberUpdate()) {
                return;
            }

            $this->processUpdate();
        } catch (Throwable $e) {
            exceptionHandler()->handle($e);
        }
    }

    /**
     * Record a block or unblock of the bot, and report whether this update was
     * one. Only private chats get here: TelegramBotAuthentication rejects
     * group and channel membership changes before the controller runs.
     *
     * BotUpdateReceived still fires, so listeners see the update, but nothing
     * further is routed.
     */
    private function processChatMemberUpdate(): bool
    {
        if (! TelegramBotAuthentication::isPrivateChatMemberUpdate()) {
            return false;
        }

        if ($context = WebhookContext::capture()) {
            botEventBus()->fire(new BotUpdateReceived($context, 'my_chat_member'));
        }

        $status = wHook()->update()->myChatMember->newChatMember?->status;

        if ($status === 'kicked') {
            botUserStatus()->markBlocked(wHook()->user());
        } elseif (in_array($status, ['member', 'administrator', 'creator'], true)) {
            botUserStatus()->markActive(wHook()->user());
        }

        return true;
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
        $updateType = $update->objectType() ?? 'unknown';

        botEventBus()->fire(new BotUpdateReceived($context, $updateType));

        $commandProcessed = false;
        $keyProcessed = false;
        $answerProcessed = false;

        if ($update->message) {
            if (str_starts_with($update->message->text, '/')) {
                [$command, $payload] = array_pad(explode(' ', $update->message->text, 2), 2, null);

                stateAnswerBus()->cancelHandler(wHook()->requestState());
                $this->cancelOldProcess();
                $commandProcessed = commandBus()->processCommands();

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

            $requestIsInvalid = ! ($commandProcessed || $keyProcessed || $answerProcessed);
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
