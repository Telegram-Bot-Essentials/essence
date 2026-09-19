<?php

namespace TelegramBotEssentials\Essence\Forms;

use Illuminate\Support\Facades\Lang;
use TelegramBotEssentials\Essence\Forms\Steps\Step;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * A multi-step data-collection flow. Subclass it, list the steps, and say
 * what happens with the answers; the engine runs the conversation (prompts,
 * Back / Next / Skip, dependent answers, a confirm step, cancelling).
 *
 *     class CreateOfferForm extends Form
 *     {
 *         protected string $type = 'OFFER_CREATE';
 *         protected string $lang = 'tbe-billing::offers.wizard';
 *
 *         public function steps(): array { return [Text::make('code'), ...]; }
 *
 *         public function onComplete(array $answers, array $ctx): TelegramResponse { ... }
 *     }
 *
 * Register it with formRegistry()->addForm() (or loadForms() for an app
 * directory) and start it from a callback with CreateOfferForm::start($ctx).
 *
 * Text lives under $lang: `fields.{step}.prompt` (required), `fields.{step}.label`,
 * `lockLabel`, `summary`, `finished`.
 */
abstract class Form
{
    /** The key stored in a user's state while the form runs. */
    protected string $type;

    /** Minimum role power needed to run it, as for a StateAnswer. */
    protected int $perm = 0;

    /** Translation prefix for this form's texts, e.g. `tbe-billing::offers.wizard`. */
    protected string $lang;

    /** Seconds without a reply after which the form expires; null = never. */
    protected ?int $idleSeconds = 86400;

    /**
     * @return list<Step>
     */
    abstract public function steps(): array;

    /**
     * Called once, on Confirm, with every applicable answer (cast, null when
     * skipped). Do the work here and return what the confirm prompt turns
     * into (typically the created thing's detail screen).
     *
     * @param  array<string, mixed>  $answers
     * @param  array<string, mixed>  $ctx  what start() was given
     */
    abstract public function onComplete(array $answers, array $ctx): TelegramResponse;

    /**
     * What the message the form was started from becomes once it completes
     * (typically the refreshed list). Null puts it back as it was.
     *
     * @param  array<string, mixed>  $ctx
     */
    public function originalScreen(array $ctx): ?TelegramResponse
    {
        return null;
    }

    /**
     * Runs after the user cancels or the form expires.
     *
     * @param  array<string, mixed>  $ctx
     */
    public function onCancel(array $ctx): void {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function idleSeconds(): ?int
    {
        return $this->idleSeconds;
    }

    /** @param  array<string, string>  $replace */
    public function text(string $key, array $replace = []): string
    {
        return __($this->lang.'.'.$key, $replace);
    }

    public function hasText(string $key): bool
    {
        return Lang::has($this->lang.'.'.$key);
    }

    /**
     * Starts the form for the current user. Called from a callback query, it
     * locks the message the button sat on and gives it back when the form
     * ends; from anywhere else there is no message to lock.
     *
     * @param  array<string, mixed>  $ctx  small scalar context handed back to onComplete()
     * @param  array<string, string>  $prefill  raw answers to start with
     */
    public static function start(array $ctx = [], array $prefill = []): void
    {
        $registry = formRegistry();

        formEngine()->start($registry->get(static::class) ?? $registry->addForm(static::class), $ctx, $prefill);
    }
}
