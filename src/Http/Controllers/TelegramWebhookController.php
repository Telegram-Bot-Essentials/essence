<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramWebhookController extends Controller
{
    use CanCancelOldProcess;

    public function __invoke(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
        App::setLocale(wHook()->bot()->settings->language);

        try {
            dependsOn(!wHook()->bot()->suspended && (is_null(wHook()->bot()->activated_until) || wHook()->bot()->activated_until->isFuture()), __('tbe::general.alerts.botIsOff'));
            if (!hasAccess()) dependsOn(wHook()->bot()->settings->bot_status, __('tbe::general.alerts.botIsOff'));
            $commands = config('tbe-essence.commands') ?? [];

            foreach ($commands as $command) {
                if (!is_subclass_of($command, Command::class))
                    throw new LogicException("ReplyKey {$command} is not a subclass of namespace Telegram\Bot\Commands\Command");
                wHook()->api()->addCommand($command);
            }
            $this->processUpdate();
        } catch (Exception $e) {
            exceptionHandler()->handle($e);
        }
    }

    /**
     * @throws LogicException
     * @throws TbeLogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function processUpdate()
    {
        $commandProcessed = false;
        $keyProcessed = false;
        $answerProcessed = false;

        if (wHook()->update()->message) {
            if (str_starts_with(wHook()->update()->message->text, '/')) {
                $this->cancelOldProcess();
                wHook()->api()->processCommand(wHook()->update());
                $commandProcessed = true;
            } else {
                $keyProcessed = replyKeyBus()->processReplyKey();
                if (wHook()->user()->state)
                    $answerProcessed = stateAnswerBus()->processStateAnswers();
            }

            $requestIsInvalid = !($commandProcessed || $keyProcessed || $answerProcessed);
            if ($requestIsInvalid) {
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => __('tbe::general.alerts.requestIsInvalid'),
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            }
        } elseif (wHook()->update()->callbackQuery) {
            callbackQueryBus()->processCallbackQueries();
        }
    }
}
