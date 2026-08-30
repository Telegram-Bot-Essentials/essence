<?php

namespace TelegramBotEssentials\Essence\Services;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Telescope\Telescope;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Exceptions\CannotSetItActive;
use TelegramBotEssentials\Essence\Exceptions\CannotSetItAsDone;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\HandlerContextExpired;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
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
            } catch (HandlerContextExpired $e) {
                $this->contextExpiredUserAlert($e);
            } catch (ModelNotFoundException $e) {
                $this->modelNotFoundUserAlert($e);
            } catch (ItemNotFoundException $e) {
                $this->itemNotFoundUserAlert($e);
            } catch (FeatureIsDisabled $e) {
                $this->featureIsDisabledUserAlert($e);
            } catch (TbeLogicException $e) {
                $this->generalAlert($e);
            }
        } catch (Throwable $e) {
            $this->tagInTelescope();
            // Only reach for the webhook to notify the user when it is fully
            // populated. A stray exception mid-request (or one thrown well
            // outside a webhook - a queued job, the scheduler, static
            // analysis) leaves wHook() half-built; dereferencing it here
            // throws again and recurses straight back into handle() until
            // the worker runs out of memory.
            if (wHook()->check()) {
                try {
                    if (wHook()->update()->inlineQuery) {
                        $this->answerInlineQueryWithError();
                    } else {
                        wHook()->api()->sendMessage([
                            'chat_id' => wHook()->user()->telegramUser->peer_id,
                            'text' => '😭 Something went wrong, please contact the bot support',
                            'reply_markup' => wHook()->user()->getKeyboard(),
                        ]);
                    }
                } catch (Throwable $notifyError) {
                    tbeLog('essence')->error('Failed to notify user about an error: '.$notifyError->getMessage(), ['exception' => $notifyError]);
                }
            }
            exceptionReport($e);
            abort(203, 'Something went wrong');
        }
    }

    /**
     * Tag the current Telescope entry as a BUG so unhandled exceptions can
     * be filtered in the Telescope UI.
     *
     * Telescope is an optional dev dependency, so this is a no-op in apps
     * that do not install it.
     */
    private function tagInTelescope(): void
    {
        if (! class_exists(Telescope::class)) {
            return;
        }

        Telescope::tag(fn () => ['BUG']);
    }

    /**
     * @throws TelegramSDKException
     */
    private function invalidPageNumberUserAlert(InvalidPageNumber|Exception $e): void
    {
        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage() == '' ? __('tbe::general.alerts.invalidPageNumber') : $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage() == '' ? __('tbe::general.alerts.invalidPageNumber') : $e->getMessage(),
            ]);
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    private function validationExceptionUserAlert(ValidationException $e): void
    {
        tbeLog('essence')->warning('Validation failed: '.$e->getMessage());
        if (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();

            return;
        }
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $e->getMessage(),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function cannotSetItActiveUserAlert(CannotSetItActive $e): void
    {
        tbeLog('essence')->warning('Cannot set it active: '.$e->getMessage());
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
        tbeLog('essence')->warning('Cannot set it as done: '.$e->getMessage());
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
        tbeLog('essence')->warning('Model not found: '.$e->getMessage(), ['model' => $e->getModel()]);
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
     * A multi-step flow tried to resume against a MessageMeta/StateData row
     * that has since been pruned. Clear the now-unresumable state and tell
     * the user to start over rather than letting the null dereference that
     * raised this crash the request.
     *
     * @throws TelegramSDKException
     */
    private function contextExpiredUserAlert(HandlerContextExpired $e): void
    {
        tbeLog('essence')->warning('Handler context expired: '.($e->getMessage() ?: 'no message'));

        if (wHook()->check()) {
            wHook()->user()->changeState();
        }

        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe::general.alerts.contextExpired'),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->peerId(),
                'text' => __('tbe::general.alerts.contextExpired'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function featureIsDisabledUserAlert(FeatureIsDisabled|Exception $e): void
    {
        if ($e instanceof FeatureIsDisabled && ($response = $e->getResponse())) {
            if (wHook()->update()->callbackQuery) {
                $response->update();
            } else {
                $response->send();
            }

            return;
        }

        if (wHook()->update()->callbackQuery) {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $e->getMessage() == '' ? __('tbe::general.alerts.disabledFeature', ['feature' => getInputInlineKeyText()]) : $e->getMessage(),
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        } elseif (wHook()->update()->inlineQuery) {
            $this->answerInlineQueryWithError();
        } else {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => $e->getMessage() == '' ? __('tbe::general.alerts.disabledFeature', ['feature' => wHook()->update()?->message?->text ?? 'N/A']) : $e->getMessage(),
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
        tbeLog('essence')->warning(get_class($e).': '.$e->getMessage());
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
        tbeLog('essence')->warning('Item not found: '.($e->getMessage() ?: 'no message'));
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
