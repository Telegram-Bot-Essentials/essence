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
use TelegramBotEssentials\Essence\Events\BotTextMatcherHandled;
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

        if ($update->message) {
            $this->routeMessage();
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

    /** Whether "/command@name" names a bot other than this one. */
    private function isForAnotherBot(string $command): bool
    {
        $at = strpos($command, '@');
        if ($at === false) {
            return false;
        }

        $username = wHook()->bot()->username();

        return $username !== null && strcasecmp(substr($command, $at + 1), $username) !== 0;
    }

    /**
     * Route a message update: a command, a reply key, an answer to the open
     * flow, or a text matcher, in that order. Public so a pressed group
     * keyboard button can be replayed here as if its text had been typed.
     *
     * @throws LogicException
     * @throws TbeLogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    public function routeMessage(): void
    {
        $context = WebhookContext::capture();
        $message = wHook()->update()->message;

        if ($message === null) {
            return;
        }

        $commandProcessed = false;
        $keyProcessed = false;
        $answerProcessed = false;
        $matcherProcessed = false;

        if (str_starts_with((string) $message->text, '/')) {
            $parts = explode(' ', (string) $message->text, 2);
            $command = $parts[0];
            $payload = $parts[1] ?? null;

            // In a group, /command@otherbot belongs to another bot: leave
            // it alone rather than cancel this user's flow over it.
            if ($this->isForAnotherBot($command)) {
                return;
            }

            stateAnswerBus()->cancelHandler(wHook()->requestState());
            $this->cancelOldProcess();
            $commandProcessed = commandBus()->processCommands();

            if ($payload !== null && strtolower($command) === '/start') {
                botEventBus()->fire(new BotDeepLinkReceived($context, $payload));
            }
        } else {
            $keyProcessed = replyKeyBus()->processReplyKey();
            if ($keyProcessed) {
                botEventBus()->fire(new BotReplyKeyHandled($context, $message->text));
            } elseif (wHook()->conversation()->state) {
                $answerProcessed = stateAnswerBus()->processStateAnswers();
                if ($answerProcessed) {
                    botEventBus()->fire(new BotStateAnswerHandled($context, wHook()->requestState()));
                }
            }

            // Last resort, so a matcher never steals a button or a form answer.
            if (! $keyProcessed && ! $answerProcessed) {
                $matcher = textMatcherBus()->processTextMatchers();
                if ($matcher !== null) {
                    $matcherProcessed = true;
                    botEventBus()->fire(new BotTextMatcherHandled($context, $matcher));
                }
            }
        }

        $requestIsInvalid = ! ($commandProcessed || $keyProcessed || $answerProcessed || $matcherProcessed);
        if ($requestIsInvalid) {
            botEventBus()->fire(new BotUpdateUnhandled($context));

            // A group is mostly people talking to each other: what no
            // handler claims there is not addressed to the bot.
            if (wHook()->inGroup()) {
                return;
            }

            wHook()->api()->sendMessage([
                ...wHook()->replyTo(),
                'text' => __('tbe::general.alerts.requestIsInvalid'),
                'reply_markup' => replyKeyboard(),
            ]);
        }
    }
}
