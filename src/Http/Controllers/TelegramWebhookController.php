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
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;

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
        } catch (Exception $e) {
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
        if (is_dir($adminQueries)) loadCallbackQueries($adminQueries);
        if (is_dir($memberQueries)) loadCallbackQueries($memberQueries);

        if (is_dir($adminStateAnswers)) loadStateAnswers($adminStateAnswers);
        if (is_dir($memberStateAnswers)) loadStateAnswers($memberStateAnswers);

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
