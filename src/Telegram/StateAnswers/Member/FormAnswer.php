<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers\Member;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Exceptions\HandlerContextExpired;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\FormState;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

/**
 * The state every running Form puts its user in. It carries no logic of its
 * own: the form key and progress are in the state's params, and the engine
 * does the work.
 */
class FormAnswer extends StateAnswer
{
    protected string $type = FormState::TYPE;

    protected int $perm = 0;

    /** @return list<string> */
    public function getAllowedFields(): array
    {
        // Cancelling arrives as a plain text key press whatever step is open.
        return $this->method === 'cancel'
            ? [AllowableFields::TEXT->value, AllowableFields::PHOTO->value, AllowableFields::DOCUMENT->value]
            : [AllowableFields::TEXT->value];
    }

    public function keyboard(): ?Keyboard
    {
        $resolved = $this->resolve();

        return $resolved === null ? null : formEngine()->keyboard(...$resolved);
    }

    public function answer(): void
    {
        [$form, $state] = $this->resolve() ?? throw new HandlerContextExpired;

        formEngine()->handleMessage($form, $state, wHook()->update()->getMessage());
    }

    public function cancel(): void
    {
        $resolved = $this->resolve();

        if ($resolved !== null) {
            formEngine()->cancel(...$resolved);
        }
    }

    /** @return array{0: Form, 1: FormState}|null */
    private function resolve(): ?array
    {
        $state = FormState::fromParams($this->params);

        if ($state === null) {
            return null;
        }

        $form = formRegistry()->get($state->form);

        return $form === null ? null : [$form, $state];
    }
}
