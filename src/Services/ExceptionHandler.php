<?php

namespace TelegramBotEssentials\Essence\Services;

use TelegramBotEssentials\Essence\Exceptions\CannotSetItActive;
use TelegramBotEssentials\Essence\Exceptions\CannotSetItAsDone;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Telescope\Telescope;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

class ExceptionHandler
{
    public function handle(Throwable $e): void
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
        } catch (Throwable $e) {
            Telescope::tag(fn() => ['BUG']);
            try {
                if (wHook()->update()->inlineQuery) {
                    $this->answerInlineQueryWithError();
                } else {
                    wHook()->api()->sendMessage([
                        'chat_id' => wHook()->user()->telegramUser->peer_id,
                        'text' => "😭 Something went wrong, please contact the bot support",
                        'reply_markup' => wHook()->user()->getKeyboard(),
                    ]);
                }
            } catch (Throwable $e) {
                tbeLog('essence')->error('Failed to notify user about an error: ' . $e->getMessage(), ['exception' => $e]);
            }
            exceptionReport($e);
            abort(203, 'Something went wrong');
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
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
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
        tbeLog('essence')->warning('Validation failed: ' . $e->getMessage());
        if (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
            return;
        }
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
        tbeLog('essence')->warning('Cannot set it active: ' . $e->getMessage());
        if (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
            return;
        }
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
        tbeLog('essence')->warning('Cannot set it as done: ' . $e->getMessage());
        if (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
            return;
        }
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
        tbeLog('essence')->warning('Model not found: ' . $e->getMessage(), ['model' => $e->getModel()]);
        $resourceName = getResourceName($e->getModel());
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe::general.alerts.notFound', ['resource' => $resourceName]),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
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
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
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
        tbeLog('essence')->warning(get_class($e) . ': ' . $e->getMessage());
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage(),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }

    private function answerInlineQueryWithError(): void
    {
        try {
            wHook()->api()->answerInlineQuery([
                'inline_query_id' => wHook()->update()->inlineQuery->id,
                'results' => '[]',
                'cache_time' => 0,
            ]);
        } catch (Throwable) {
        }
    }

    private function itemNotFoundUserAlert(ItemNotFoundException $e)
    {
        tbeLog('essence')->warning('Item not found: ' . ($e->getMessage() ?: 'no message'));
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => 'Requested item not found.',
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => 'Requested item not found.',
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }
}
