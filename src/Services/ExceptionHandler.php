<?php

namespace Elyar\TelegramBotEssentials\Services;

use Elyar\TelegramBotEssentials\Exceptions\CannotSetItActive;
use Elyar\TelegramBotEssentials\Exceptions\CannotSetItAsDone;
use Elyar\TelegramBotEssentials\Exceptions\FeatureIsDisabled;
use Elyar\TelegramBotEssentials\Exceptions\InvalidPageNumber;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Exceptions\TbeLogicException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Telescope\Telescope;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ExceptionHandler
{
    public function handle(Exception $e): void
    {
        try {
            try {
                throw $e;
            } catch (InvalidPageNumber $e) {
                $this->invalidPageNumberUserAlert($e);
            } catch (ValidationException $e) {
                $this->validationExceptionUserAlert($e);
            } catch (CannotSetItActive $e) {
                $this->cannotSetItActiveUserAlert($e);
            } catch (CannotSetItAsDone $e) {
                $this->cannotSetItAsDoneUserAlert($e);
            } catch (ModelNotFoundException $e) {
                $this->modelNotFoundUserAlert($e);
            } catch (ItemNotFoundException $e){
                $this->itemNotFoundUserAlert($e);
            } catch (FeatureIsDisabled $e) {
                $this->featureIsDisabledUserAlert($e);
            } catch (TbeLogicException $e) {
                $this->generalAlert($e);
            }
        } catch (TelegramSDKException|Exception $e) {
            Telescope::tag(fn() => ['BUG']);
            try {
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => "😭 Something went wrong, please contact the bot support",
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
            exceptionReport($e);
            abort(200, 'Something went wrong');
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function invalidPageNumberUserAlert(InvalidPageNumber|Exception $e): void
    {
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage() == "" ? __('tbe::general.alerts.invalidPageNumber') : $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage() == "" ? __('tbe::general.alerts.invalidPageNumber') : $e->getMessage(),
            ]);
        }
    }

    /**
     * @param ValidationException $e
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function validationExceptionUserAlert(ValidationException $e): void
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $e->getMessage(),
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function cannotSetItActiveUserAlert(CannotSetItActive $e): void
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
    private function cannotSetItAsDoneUserAlert(CannotSetItAsDone $e): void
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
    public function modelNotFoundUserAlert(ModelNotFoundException $e): void
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        $resourceName = getResourceName($e->getModel());
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe::general.alerts.notFound', ['resource' => $resourceName]),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => __('tbe::general.alerts.notFound', ['resource' => $resourceName]),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function featureIsDisabledUserAlert(FeatureIsDisabled|Exception $e): void
    {
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage() == "" ? __('tbe::general.alerts.disabledFeature', ['feature' => getInputInlineKeyText()]) : $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage() == "" ? __('tbe::general.alerts.disabledFeature', ['feature' => wHook()->update()?->message?->text ?? "N/A"]) : $e->getMessage(),
            ]);
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     * @throws LogicException
     */
    private function generalAlert(Exception $e): void
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage(),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }

    private function itemNotFoundUserAlert(ItemNotFoundException $e)
    {
        Log::error($e->getMessage() ?? 'error message is not provided');
        Log::error(json_encode(wHook()->update(), JSON_PRETTY_PRINT) ?? 'Update is not provided');
        Log::error($e->getTraceAsString() ?? 'Trace is not provided');
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => 'Requested item not found.',
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => 'Requested item not found.',
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }
}
