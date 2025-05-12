<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\CannotSetItActive;
use Elyar\TelegramBotEssentials\Exceptions\CannotSetItAsDone;
use Elyar\TelegramBotEssentials\Exceptions\FeatureIsDisabled;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Traits\CanCancelOldProcess;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Routing\Controller;
class TelegramWebhookController extends Controller
{
    use CanCancelOldProcess;

    public function __invoke(Request $request, string $unique_id)
    {
        $request->headers->set('Accept', 'application/json');
        try {
            try {
                if (wHook()->user()->power < Roles::ADMIN->value) dependsOn(wHook()->bot()->settings->bot_status, __('telegram-bot-essentials::bot_settings.botIsOffAlertMessage'));
                $this->initializeOptions();
                $this->processUpdate();
            } catch (ValidationException $e) {
                $this->validationExceptionUserAlert($e);
            } catch (CannotSetItActive $e) {
                $this->cannotSetItActiveUserAlert($e);
            } catch (CannotSetItAsDone $e) {
                $this->cannotSetItAsDoneUserAlert($e);
            } catch (ModelNotFoundException $e) {
                $this->modelNotFoundUserAlert($e);
            } catch (FeatureIsDisabled $e) {
                $this->featureIsDisabledUserAlert($e);
            }
        } catch (TelegramSDKException|Exception $e) {
            Log::error($e->getMessage() ?? 'error message is not provided');
            Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
            Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        }
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function initializeOptions()
    {
        $commands = config('telegram-bot-essentials.commands') ?? [];

        foreach ($commands as $command) {
            if (!is_subclass_of($command, Command::class))
                throw new LogicException("ReplyKey {$command} is not a subclass of namespace Telegram\Bot\Commands\Command");
            wHook()->api()->addCommand($command);
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
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
                    'text' => __('telegram-bot-essentials::general.alerts.requestIsInvalid'),
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            }
        } elseif (wHook()->update()->callbackQuery) {
            callbackQueryBus()->processCallbackQueries();
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function validationExceptionUserAlert(ValidationException $e)
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $e->getMessage(),
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function cannotSetItAsDoneUserAlert(CannotSetItAsDone $e)
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $e->getMessage(),
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function cannotSetItActiveUserAlert(CannotSetItActive $e)
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $e->getMessage(),
        ]);
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    public function modelNotFoundUserAlert(ModelNotFoundException $e)
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        $parts = preg_split('/\\\\/', $e->getModel());
        if (wHook()->update()->message) {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->update()->message->from->id,
                'text' => __('telegram-bot-essentials::general.alerts.notFound', ['resource' => end($parts)]),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        } elseif (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('telegram-bot-essentials::general.alerts.notFound', ['resource' => end($parts)]),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function featureIsDisabledUserAlert(FeatureIsDisabled|Exception $e)
    {
        if (wHook()->update()->message) {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->update()->message->from->id,
                'text' => $e->getMessage() == "" ? __('telegram-bot-essentials::general.alerts.disabledFeatureAlert', ['feature' => wHook()->update()->message->text]) : $e->getMessage(),
            ]);
        } elseif (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage() == "" ? __('telegram-bot-essentials::general.alerts.disabledFeatureAlert', ['feature' => getInputInlineKeyText()]) : $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        }
    }
}
