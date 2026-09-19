<?php

namespace TelegramBotEssentials\Essence\Forms;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Step;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyInterface;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * Runs a Form's conversation.
 *
 * The user's whole progress lives in their state (see FormState). The chat is
 * a plain conversation: each step is a prompt carrying that step's reply
 * keyboard, the user answers, the bot answers back with what it understood
 * and asks the next question. No message is edited or deleted along the way -
 * a message that carries a reply keyboard cannot be edited, and Telegram
 * refuses to delete anything older than 48 hours, so a flow that can sit open
 * for days depends on neither. The only edits are on inline messages, which
 * Telegram does allow: paging through a choice's options and closing them once
 * one is picked, and the message the form was started from.
 *
 * Which answers count is decided on every read by walk(), never by deleting
 * them: an answer whose step no longer applies is simply ignored, and comes
 * back if the earlier answer that hid it is changed back.
 */
class FormEngine
{
    private const PAGE_SIZE = 8;

    /**
     * Starts a form for the current user and sends its first prompt.
     *
     * @param  array<string, mixed>  $ctx
     * @param  array<string, string>  $prefill
     */
    public function start(Form $form, array $ctx = [], array $prefill = []): void
    {
        if (! hasAccess($form->getPerm())) {
            return;
        }

        $steps = $this->steps($form);
        $meta = null;

        if (wHook()->update()->callbackQuery) {
            $meta = MessageMeta::makeWithCurrentMessage();
            $meta->lockAction($form->hasText('lockLabel') ? $form->text('lockLabel') : __('tbe::forms.locked'));
        }

        $state = new FormState(
            form: $form->getType(),
            step: '',
            answers: array_map(strval(...), $prefill),
            meta: $meta?->id,
            ctx: $ctx,
        );

        $this->present($form, $state, $steps);
    }

    /**
     * Handles a text message sent while the form is open: a button label or
     * an answer.
     *
     * @param  Collection<string, mixed>  $message
     *
     * @throws ValidationException
     */
    public function handleMessage(Form $form, FormState $state, Collection $message): void
    {
        if (! hasAccess($form->getPerm())) {
            wHook()->user()->changeState();

            return;
        }

        if ($this->expireIfIdle($form, $state)) {
            return;
        }

        $steps = $this->steps($form);
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $position = $this->position($state, $applicable);
        $sent = data_get($message, 'text');
        $text = is_string($sent) ? trim($sent) : '';
        $labels = $this->buttonLabels();

        if ($state->step === FormState::CONFIRM) {
            match ($text) {
                $labels['confirm'] => $this->confirm($form, $state, $steps),
                $labels['back'] => $this->back($form, $state, $steps),
                default => throw ValidationException::withMessages(['step' => __('tbe::forms.errors.useButtons')]),
            };

            return;
        }

        if (! isset($applicable[$position])) {
            $this->present($form, $state, $steps);

            return;
        }

        $step = $applicable[$position];

        if ($text === $labels['back'] && $position > 0) {
            $this->back($form, $state, $steps);

            return;
        }

        if ($text === $labels['next'] && $this->canNext($state, $applicable, $effective)) {
            $this->next($form, $state, $steps);

            return;
        }

        if ($text === $labels['finish'] && $this->canFinish($state, $applicable, $effective)) {
            $this->finish($form, $state, $steps);

            return;
        }

        if ($step->isSkippable() && $text === $this->skipLabel($step, $effective)) {
            $this->submit($form, $state, $steps, $step, null);

            return;
        }

        if ($step instanceof Choice) {
            $value = $step->isInline() ? null : $step->valueForLabel($text);

            if ($value === null) {
                throw ValidationException::withMessages([$step->key => __($step->isInline() ? 'tbe::forms.errors.useButtons' : 'tbe::forms.errors.invalidChoice')]);
            }

            $this->submit($form, $state, $steps, $step, $value);

            return;
        }

        $this->submit($form, $state, $steps, $step, $text);
    }

    /**
     * An inline option was tapped. False when the tap is stale (another step
     * is open, or the option is gone), so the caller can say so.
     */
    public function handlePick(Form $form, FormState $state, int $stepIndex, string $value): bool
    {
        if (! hasAccess($form->getPerm())) {
            return false;
        }

        if ($this->expireIfIdle($form, $state)) {
            return true;
        }

        $steps = $this->steps($form);
        [$applicable] = $this->walk($steps, $state->answers);

        if ($state->step === FormState::CONFIRM || $this->position($state, $applicable) !== $stepIndex) {
            return false;
        }

        $step = $applicable[$stepIndex];

        if (! $step instanceof Choice || ! $step->isInline() || ! array_key_exists($value, $step->resolveOptions())) {
            return false;
        }

        $this->submit($form, $state, $steps, $step, $value);

        return true;
    }

    /** The options message for an inline choice at $page (1-based), for first send and paging. */
    public function optionsResponse(Form $form, FormState $state, int $stepIndex, int $page): ?TelegramResponse
    {
        [$applicable] = $this->walk($this->steps($form), $state->answers);
        $step = $applicable[$stepIndex] ?? null;

        if (! $step instanceof Choice || ! $step->isInline() || $this->position($state, $applicable) !== $stepIndex) {
            return null;
        }

        return new TelegramResponse(
            text: __('tbe::forms.prompt.pickOption'),
            replyMarkup: $this->optionsKeyboard($step, $stepIndex, $page),
        );
    }

    /** The reply keyboard for the step the user is on. */
    public function keyboard(Form $form, FormState $state): Keyboard
    {
        $steps = $this->steps($form);
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $labels = $this->buttonLabels();
        $cancel = __('tbe::cancel_process.reply_key');
        $rows = [];
        $placeholder = null;

        $position = $this->position($state, $applicable);

        if ($state->step === FormState::CONFIRM || ! isset($applicable[$position])) {
            $rows[] = [$labels['confirm'], $labels['back']];
        } else {
            $step = $applicable[$position];
            $placeholder = $step->getPlaceholder();

            if ($step instanceof Choice && ! $step->isInline()) {
                $optionLabels = array_values($step->resolveOptions());
                $this->assertNoLabelCollisions($optionLabels, $labels + ['cancel' => $cancel]);
                array_push($rows, ...array_chunk($optionLabels, $step->columnCount()));
            }

            $controls = [];
            if ($position > 0) {
                $controls[] = $labels['back'];
            }
            if ($this->canNext($state, $applicable, $effective)) {
                $controls[] = $labels['next'];
            }
            if ($step->isSkippable()) {
                $controls[] = $this->skipLabel($step, $effective);
            }
            if ($controls !== []) {
                $rows[] = $controls;
            }
            if ($this->canFinish($state, $applicable, $effective)) {
                $rows[] = [$labels['finish']];
            }
        }

        $rows[] = [$cancel];

        return Keyboard::make(array_filter([
            'keyboard' => $rows,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'input_field_placeholder' => $placeholder,
        ], fn ($value) => $value !== null));
    }

    /** Gives the starting message back and runs the form's cancel hook. */
    public function cancel(Form $form, FormState $state): void
    {
        $this->giveBackOriginal($state);
        $form->onCancel($state->ctx);
    }

    /**
     * @return array<string, Step> by key
     *
     * @throws LogicException
     */
    public function steps(Form $form): array
    {
        $steps = [];

        foreach ($form->steps() as $step) {
            if ($step->key === FormState::CONFIRM || isset($steps[$step->key])) {
                throw new LogicException(sprintf('Form %s has a duplicate or reserved step key "%s".', $form->getType(), $step->key));
            }
            $steps[$step->key] = $step;
        }

        return $steps;
    }

    /**
     * The steps that apply given the answers, in order, and the answers that
     * count. A step's `when` sees only the counted answers before it, so an
     * answer hidden by an earlier one cannot influence a later step.
     *
     * @param  array<string, Step>  $steps
     * @param  array<string, ?string>  $answers
     * @return array{0: list<Step>, 1: array<string, ?string>}
     */
    private function walk(array $steps, array $answers): array
    {
        $applicable = [];
        $effective = [];

        foreach ($steps as $step) {
            if (! $step->isApplicable($effective)) {
                continue;
            }

            $applicable[] = $step;

            if (array_key_exists($step->key, $answers)) {
                $effective[$step->key] = $answers[$step->key];
            }
        }

        return [$applicable, $effective];
    }

    /**
     * Index of the first applicable step without an answer, or the count
     * when everything is answered (the confirm screen).
     *
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     */
    private function frontier(array $applicable, array $effective): int
    {
        foreach ($applicable as $index => $step) {
            if (! array_key_exists($step->key, $effective)) {
                return $index;
            }
        }

        return count($applicable);
    }

    /**
     * Where the cursor is, as an index into the applicable steps; the count
     * for the confirm screen. A cursor that is no longer valid (its step no
     * longer applies, or confirm while something is unanswered) falls back
     * to the frontier.
     *
     * @param  list<Step>  $applicable
     */
    private function position(FormState $state, array $applicable): int
    {
        $frontier = $this->frontier($applicable, $this->effectiveOf($applicable, $state->answers));

        if ($state->step === FormState::CONFIRM) {
            return $frontier;
        }

        foreach ($applicable as $index => $step) {
            if ($step->key === $state->step) {
                return min($index, $frontier);
            }
        }

        return $frontier;
    }

    /**
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $answers
     * @return array<string, ?string>
     */
    private function effectiveOf(array $applicable, array $answers): array
    {
        $effective = [];

        foreach ($applicable as $step) {
            if (array_key_exists($step->key, $answers)) {
                $effective[$step->key] = $answers[$step->key];
            }
        }

        return $effective;
    }

    /**
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     */
    private function canNext(FormState $state, array $applicable, array $effective): bool
    {
        if ($state->step === FormState::CONFIRM) {
            return false;
        }

        $position = $this->position($state, $applicable);

        return isset($applicable[$position]) && array_key_exists($applicable[$position]->key, $effective);
    }

    /**
     * Whether Finish is on offer: every applicable step that must be answered
     * has been, so all that is left is optional and the user may go straight
     * to the summary.
     *
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     */
    private function canFinish(FormState $state, array $applicable, array $effective): bool
    {
        if ($state->step === FormState::CONFIRM) {
            return false;
        }

        foreach ($applicable as $step) {
            if (! $step->isSkippable() && ! array_key_exists($step->key, $effective)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The answers given before $key, as validation and conditions see them.
     *
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     * @return array<string, ?string>
     */
    private function answersBefore(array $applicable, array $effective, string $key): array
    {
        $before = [];

        foreach ($applicable as $step) {
            if ($step->key === $key) {
                break;
            }
            if (array_key_exists($step->key, $effective)) {
                $before[$step->key] = $effective[$step->key];
            }
        }

        return $before;
    }

    /**
     * Records an answer (null = skipped/cleared), then moves on.
     *
     * @param  array<string, Step>  $steps
     *
     * @throws ValidationException
     */
    private function submit(Form $form, FormState $state, array $steps, Step $step, ?string $raw): void
    {
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $label = $this->label($form, $step, $step->key);

        if ($raw === null && ! $step->isSkippable()) {
            throw ValidationException::withMessages([$step->key => __('tbe::forms.errors.useButtons')]);
        }

        if ($raw !== null) {
            $step->validate($raw, $this->answersBefore($applicable, $effective, $step->key), $label);
        }

        $had = array_key_exists($step->key, $state->answers);
        $previous = $state->answers[$step->key] ?? null;
        $state->answers[$step->key] = $raw;

        $cleared = [];
        if ($had && $previous !== $raw) {
            $cleared = [...$this->clearDependents($steps, $state, $step->key), ...$this->clearInvalid($form, $steps, $state, $step->key)];
        }

        $this->closeOptions($state);
        $this->sendEcho($form, $step, $raw);

        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $state->step = $this->stepAfter($applicable, $step->key);

        $this->present($form, $state, $steps, $this->resetNotice($form, $steps, $cleared, $step));
    }

    /**
     * Clears the answers that declare a dependency on $changed, and on
     * whatever they clear in turn.
     *
     * @param  array<string, Step>  $steps
     * @return list<string> the keys cleared
     */
    private function clearDependents(array $steps, FormState $state, string $changed): array
    {
        $cleared = [];
        $queue = [$changed];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($steps as $step) {
                if (in_array($current, $step->dependencies(), true) && array_key_exists($step->key, $state->answers)) {
                    unset($state->answers[$step->key]);
                    $cleared[] = $step->key;
                    $queue[] = $step->key;
                }
            }
        }

        return $cleared;
    }

    /**
     * After an answer changed, validates the counted answers that come after
     * it against the new situation and clears any that no longer pass (an
     * amount of 500 once the type became a percentage). Catches what a
     * declared dependency does not; repeats because clearing one answer can
     * change which steps apply.
     *
     * @param  array<string, Step>  $steps
     * @return list<string> the keys cleared
     */
    private function clearInvalid(Form $form, array $steps, FormState $state, string $changed): array
    {
        $cleared = [];

        do {
            $dirty = false;
            [$applicable, $effective] = $this->walk($steps, $state->answers);
            $after = false;

            foreach ($applicable as $step) {
                if ($step->key === $changed) {
                    $after = true;

                    continue;
                }

                $raw = $effective[$step->key] ?? null;
                if (! $after || $raw === null) {
                    continue;
                }

                try {
                    $step->validate($raw, $this->answersBefore($applicable, $effective, $step->key), $this->label($form, $step, $step->key));
                } catch (ValidationException) {
                    unset($state->answers[$step->key]);
                    $cleared[] = $step->key;
                    $dirty = true;

                    break;
                }
            }
        } while ($dirty);

        return $cleared;
    }

    /**
     * @param  array<string, Step>  $steps
     * @param  list<string>  $cleared
     */
    private function resetNotice(Form $form, array $steps, array $cleared, Step $changed): ?string
    {
        if ($cleared === []) {
            return null;
        }

        $names = array_map(fn (string $key) => $this->label($form, $steps[$key] ?? null, $key), array_values(array_unique($cleared)));

        return __('tbe::forms.notice.reset', [
            'cleared' => implode(', ', $names),
            'changed' => $this->label($form, $changed, $changed->key),
        ]);
    }

    /** @param  list<Step>  $applicable */
    private function stepAfter(array $applicable, string $key): string
    {
        foreach ($applicable as $index => $step) {
            if ($step->key === $key) {
                return isset($applicable[$index + 1]) ? $applicable[$index + 1]->key : FormState::CONFIRM;
            }
        }

        return FormState::CONFIRM;
    }

    /**
     * @param  array<string, Step>  $steps
     */
    private function next(Form $form, FormState $state, array $steps): void
    {
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $step = $applicable[$this->position($state, $applicable)] ?? null;

        if ($step === null) {
            return;
        }

        $this->closeOptions($state);
        $state->step = $this->stepAfter($applicable, $step->key);

        $this->present($form, $state, $steps);
    }

    /**
     * @param  array<string, Step>  $steps
     */
    private function back(Form $form, FormState $state, array $steps): void
    {
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $position = $this->position($state, $applicable);

        if ($position <= 0) {
            return;
        }

        $previous = $applicable[$position - 1];

        $this->closeOptions($state);
        $state->step = $previous->key;

        $this->present($form, $state, $steps);
    }

    /**
     * Skips whatever optional steps are still unanswered and shows the summary.
     * Anything the skips make newly applicable and required is caught by the
     * check Confirm runs.
     *
     * @param  array<string, Step>  $steps
     */
    private function finish(Form $form, FormState $state, array $steps): void
    {
        [$applicable, $effective] = $this->walk($steps, $state->answers);

        foreach ($applicable as $step) {
            if (! array_key_exists($step->key, $effective)) {
                $state->answers[$step->key] = null;
            }
        }

        $this->closeOptions($state);
        $state->step = FormState::CONFIRM;

        $this->present($form, $state, $steps);
    }

    /**
     * @param  array<string, Step>  $steps
     *
     * @throws ValidationException
     */
    private function confirm(Form $form, FormState $state, array $steps): void
    {
        if ($this->settleAnswers($form, $state, $steps)) {
            $this->present($form, $state, $steps, __('tbe::forms.errors.incomplete'));

            return;
        }

        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $cast = [];
        foreach ($applicable as $step) {
            $cast[$step->key] = $step->castAnswer($effective[$step->key] ?? null, $effective);
        }

        $result = $form->onComplete($cast, $state->ctx);

        wHook()->user()->changeState();

        $result->send(wHook()->peerId());

        $this->giveBackOriginal($state, $form);

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => $form->hasText('finished') ? $form->text('finished') : __('tbe::forms.finished'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    /**
     * A last check before completing: every applicable step must be answered
     * and its answer still valid. Clears whatever is not and points the
     * cursor at the first problem.
     *
     * @param  array<string, Step>  $steps
     * @return bool whether something was wrong
     */
    private function settleAnswers(Form $form, FormState $state, array $steps): bool
    {
        $wrong = false;

        do {
            $dirty = false;
            [$applicable, $effective] = $this->walk($steps, $state->answers);

            foreach ($applicable as $step) {
                if (! array_key_exists($step->key, $effective)) {
                    $wrong = true;

                    continue;
                }

                $raw = $effective[$step->key];
                if ($raw === null) {
                    continue;
                }

                try {
                    $step->validate($raw, $this->answersBefore($applicable, $effective, $step->key), $this->label($form, $step, $step->key));
                } catch (ValidationException) {
                    unset($state->answers[$step->key]);
                    $wrong = true;
                    $dirty = true;

                    break;
                }
            }
        } while ($dirty);

        if ($wrong) {
            $state->step = '';
        }

        return $wrong;
    }

    /**
     * Sends the prompt for the step the cursor is on and saves the state.
     * The state is saved only after the messages went out, so a failed send
     * leaves the user where they were.
     *
     * @param  array<string, Step>  $steps
     */
    private function present(Form $form, FormState $state, array $steps, ?string $notice = null): void
    {
        [$applicable, $effective] = $this->walk($steps, $state->answers);
        $position = $this->position($state, $applicable);
        $state->at = time();
        $state->step = isset($applicable[$position]) ? $applicable[$position]->key : FormState::CONFIRM;
        $keyboard = $this->keyboard($form, $state);

        if ($state->step === FormState::CONFIRM) {
            $this->send($this->summaryText($form, $applicable, $effective, $notice), $keyboard);
        } else {
            $step = $applicable[$position];
            $this->send($this->promptText($form, $step, $applicable, $effective, $notice), $keyboard);

            if ($step instanceof Choice && $step->isInline()) {
                $options = $this->optionsResponse($form, $state, $position, 1);
                $state->options = $this->send((string) $options?->text, $options?->replyMarkup);
            }
        }

        wHook()->user()->changeState($state->toStateString());
    }

    /**
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     */
    private function summaryText(Form $form, array $applicable, array $effective, ?string $notice): string
    {
        $lines = [];

        foreach ($applicable as $step) {
            $raw = $effective[$step->key] ?? null;
            $lines[] = '<b>'.e($this->label($form, $step, $step->key)).'</b>: '
                .($raw === null ? __('tbe::forms.prompt.none') : e($step->displayAnswer($raw)));
        }

        $title = $form->hasText('summary') ? $form->text('summary') : __('tbe::forms.summary.title');

        return ($notice !== null ? $notice."\n\n" : '').'<b>'.$title.'</b>'."\n\n".implode("\n", $lines);
    }

    /**
     * @param  list<Step>  $applicable
     * @param  array<string, ?string>  $effective
     */
    private function promptText(Form $form, Step $step, array $applicable, array $effective, ?string $notice): string
    {
        $text = ($notice !== null ? $notice."\n\n" : '')
            .'<b>'.e($this->label($form, $step, $step->key)).'</b>'."\n"
            .$this->promptFor($form, $step, $this->answersBefore($applicable, $effective, $step->key))."\n\n"
            .'<i>'.e($step->hintLine($this->answersBefore($applicable, $effective, $step->key))).'</i>';

        $current = $effective[$step->key] ?? null;
        if ($current !== null) {
            $text .= "\n\n".__('tbe::forms.prompt.current', ['value' => '<b>'.e($step->displayAnswer($current)).'</b>']);
        }

        return $text;
    }

    /** The bot's reply to an answer: what it understood, before it asks the next question. */
    private function sendEcho(Form $form, Step $step, ?string $raw): void
    {
        $answer = $raw === null
            ? '<i>'.__('tbe::forms.prompt.skipped').'</i>'
            : __('tbe::forms.prompt.answer', ['value' => '<b>'.e($step->displayAnswer($raw)).'</b>']);

        $this->send('<b>'.e($this->label($form, $step, $step->key)).'</b> '.$answer, null);
    }

    /**
     * Closes the inline options of the choice step being left, so their
     * buttons stop looking live. Inline messages can be edited, and a tap left
     * on one that could not be closed is caught as outdated anyway.
     */
    private function closeOptions(FormState $state): void
    {
        $messageId = $state->options;
        $state->options = null;

        if ($messageId === null) {
            return;
        }

        try {
            wHook()->api()->editMessageText([
                'chat_id' => wHook()->peerId(),
                'message_id' => $messageId,
                'text' => __('tbe::forms.prompt.picked'),
            ]);
        } catch (Exception $e) {
            if (! str_contains($e->getMessage(), 'message is not modified') && ! str_contains($e->getMessage(), 'message to edit not found')) {
                exceptionReport($e);
            }
        }
    }

    /**
     * Puts the message the form was started from back: the form's own
     * replacement when it completed, otherwise as it was before locking.
     */
    private function giveBackOriginal(FormState $state, ?Form $completed = null): void
    {
        $meta = $state->meta !== null ? MessageMeta::find($state->meta) : null;

        if ($meta === null) {
            return;
        }

        $screen = $completed?->originalScreen($state->ctx);

        $screen !== null ? $meta->updateAndContinueAction($screen) : $meta->continueAction();
    }

    private function expireIfIdle(Form $form, FormState $state): bool
    {
        $idle = $form->idleSeconds();

        if ($idle === null || $state->at === 0 || time() - $state->at <= $idle) {
            return false;
        }

        $this->cancel($form, $state);
        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe::forms.expired'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        return true;
    }

    private function optionsKeyboard(Choice $step, int $stepIndex, int $page): Keyboard
    {
        $options = $step->resolveOptions();
        $pages = max(1, (int) ceil(count($options) / self::PAGE_SIZE));
        $page = min(max(1, $page), $pages);

        $buttons = [];
        foreach (array_slice($options, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE, true) as $value => $label) {
            $buttons[] = Keyboard::inlineButton([
                'text' => $label,
                'callback_data' => encodeCallback(FormState::TYPE, 'pick', [$stepIndex, (string) $value]),
            ]);
        }

        $keyboard = Keyboard::make()->inline();
        foreach (array_chunk($buttons, $step->columnCount()) as $row) {
            $keyboard->row($row);
        }

        if ($pages > 1) {
            $keyboard->row([
                Keyboard::inlineButton(['text' => '‹', 'callback_data' => encodeCallback(FormState::TYPE, 'page', [$stepIndex, max(1, $page - 1)])]),
                Keyboard::inlineButton(['text' => "{$page}/{$pages}", 'callback_data' => encodeCallback(FormState::TYPE, 'noop')]),
                Keyboard::inlineButton(['text' => '›', 'callback_data' => encodeCallback(FormState::TYPE, 'page', [$stepIndex, min($pages, $page + 1)])]),
            ]);
        }

        return $keyboard;
    }

    /** @return array{back: string, next: string, skip: string, clear: string, confirm: string, finish: string} */
    private function buttonLabels(): array
    {
        return [
            'back' => __('tbe::forms.buttons.back'),
            'next' => __('tbe::forms.buttons.next'),
            'skip' => __('tbe::forms.buttons.skip'),
            'clear' => __('tbe::forms.buttons.clear'),
            'confirm' => __('tbe::forms.buttons.confirm'),
            'finish' => __('tbe::forms.buttons.finish'),
        ];
    }

    /** @param  array<string, ?string>  $effective */
    private function skipLabel(Step $step, array $effective): string
    {
        return __(($effective[$step->key] ?? null) !== null ? 'tbe::forms.buttons.clear' : 'tbe::forms.buttons.skip');
    }

    /** @param  array<string, ?string>  $answersBefore */
    private function promptFor(Form $form, Step $step, array $answersBefore): string
    {
        return $step->customPrompt($answersBefore) ?? $form->text('fields.'.$step->key.'.prompt');
    }

    private function label(Form $form, ?Step $step, string $key): string
    {
        return $step !== null && $form->hasText('fields.'.$key.'.label')
            ? $form->text('fields.'.$key.'.label')
            : Str::headline($key);
    }

    /**
     * A choice option that reads the same as a control button or a
     * registered reply key would be taken for that button, so a form that
     * defines one is broken. Checked in debug only.
     *
     * @param  list<string>  $optionLabels
     * @param  array<string, string>  $controls
     */
    private function assertNoLabelCollisions(array $optionLabels, array $controls): void
    {
        if (! config('app.debug')) {
            return;
        }

        $taken = array_values($controls);
        foreach (replyKeyBus()->getReplyKeys() as $replyKey) {
            if ($replyKey instanceof ReplyKeyInterface) {
                $taken[] = $replyKey->getText();
            }
        }

        $collisions = array_intersect($optionLabels, $taken);
        if ($collisions !== []) {
            throw new LogicException('A form choice option collides with a button label: '.implode(', ', $collisions));
        }
    }

    private function send(string $text, mixed $replyMarkup): int
    {
        $message = wHook()->api()->sendMessage(array_filter([
            'chat_id' => wHook()->peerId(),
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup,
        ], fn ($value) => $value !== null));

        return $message->messageId;
    }
}
