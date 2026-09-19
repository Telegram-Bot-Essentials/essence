<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Essence\Forms\FormState;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

/**
 * The inline buttons of a form's dynamic choice steps: picking an option and
 * paging through them. Every tap is checked against the step the user is
 * actually on, so a button left on an old message does nothing.
 */
class FormQuery extends CallbackQuery
{
    protected string $type = FormState::TYPE;

    protected int $perm = 0;

    public function pick(int $step, string $value): void
    {
        $state = FormState::fromStateString(wHook()->requestState());
        $form = $state !== null ? formRegistry()->get($state->form) : null;

        if ($state === null || $form === null || ! formEngine()->handlePick($form, $state, $step, $value)) {
            $this->answer(__('tbe::forms.errors.outdated'));

            return;
        }

        $this->answer();
    }

    public function page(int $step, int $page): void
    {
        $state = FormState::fromStateString(wHook()->requestState());
        $form = $state !== null ? formRegistry()->get($state->form) : null;
        $response = $state !== null && $form !== null ? formEngine()->optionsResponse($form, $state, $step, $page) : null;

        if ($response === null) {
            $this->answer(__('tbe::forms.errors.outdated'));

            return;
        }

        $response->update();
        $this->answer();
    }

    public function noop(): void
    {
        $this->answer();
    }
}
