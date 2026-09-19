<?php

namespace TelegramBotEssentials\Essence\Forms\Steps;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Essence\Forms\RuleHints;

/**
 * One question of a Form. Steps are built fresh from Form::steps() on every
 * request (so labels resolve in the current locale) and are configured
 * fluently:
 *
 *     Text::make('amount')->rules(['numeric', 'min:1'])->skippable()
 *
 * An answer is stored as the raw string the user sent; cast() only shapes the
 * value handed to Form::onComplete(), which keeps the stored state plain and
 * JSON-safe.
 */
abstract class Step
{
    protected bool $skippable = false;

    /** @var (Closure(array<string, ?string>): bool)|null */
    protected ?Closure $when = null;

    /** @var list<string> */
    protected array $dependsOn = [];

    /** @var array<mixed>|(Closure(array<string, ?string>): array<mixed>)|null */
    protected array|Closure|null $rules = null;

    protected ?string $hint = null;

    protected ?string $placeholder = null;

    /** @var (Closure(string, array<string, ?string>): mixed)|null */
    protected ?Closure $cast = null;

    /** @var (Closure(string): string)|null */
    protected ?Closure $display = null;

    final protected function __construct(public readonly string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    /** The user may leave this step empty; its answer is then null. */
    public function skippable(bool $skippable = true): static
    {
        $this->skippable = $skippable;

        return $this;
    }

    /**
     * Only ask this step when the condition holds. It receives the answers
     * given so far (of applicable steps only) and returns a bool.
     *
     * @param  Closure(array<string, ?string>): bool  $condition
     */
    public function when(Closure $condition): static
    {
        $this->when = $condition;

        return $this;
    }

    /**
     * Steps whose answer this one's meaning depends on. Changing any of them
     * to a different value clears this answer (and, transitively, whatever
     * depends on this one), so it must be asked again.
     */
    public function dependsOn(string ...$keys): static
    {
        $this->dependsOn = array_values($keys);

        return $this;
    }

    /**
     * Laravel validation rules, or a closure receiving the answers given so
     * far and returning them, for rules that depend on an earlier answer.
     *
     * @param  array<mixed>|Closure(array<string, ?string>): array<mixed>  $rules
     */
    public function rules(array|Closure $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /** Replaces the hint derived from the rules; needed for closure or object rules. */
    public function hint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    /** The grey text shown in Telegram's input box while this step is open. */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Shapes the stored raw answer for Form::onComplete().
     *
     * @param  Closure(string, array<string, ?string>): mixed  $cast
     */
    public function cast(Closure $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    /**
     * How the raw answer reads in prompts and the summary.
     *
     * @param  Closure(string): string  $display
     */
    public function display(Closure $display): static
    {
        $this->display = $display;

        return $this;
    }

    public function isSkippable(): bool
    {
        return $this->skippable;
    }

    /** @return list<string> */
    public function dependencies(): array
    {
        return $this->dependsOn;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /** @param  array<string, ?string>  $answers */
    public function isApplicable(array $answers): bool
    {
        return $this->when === null || (bool) ($this->when)($answers);
    }

    /** @param  array<string, ?string>  $answers */
    public function castAnswer(?string $raw, array $answers): mixed
    {
        if ($raw === null || $this->cast === null) {
            return $raw;
        }

        return ($this->cast)($raw, $answers);
    }

    public function displayAnswer(string $raw): string
    {
        return $this->display !== null ? ($this->display)($raw) : $raw;
    }

    /**
     * Throws a ValidationException, with the first message worded for the
     * user, when the value is not acceptable given the answers so far.
     *
     * @param  array<string, ?string>  $answers
     *
     * @throws ValidationException
     */
    public function validate(string $value, array $answers, string $label): void
    {
        $validator = Validator::make(
            [$this->key => $value],
            [$this->key => $this->effectiveRules($answers)],
            $this->validationMessages(),
            [$this->key => $label],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([$this->key => $validator->errors()->first($this->key)]);
        }
    }

    /**
     * The one line under a prompt telling the user what is expected.
     *
     * @param  array<string, ?string>  $answers
     */
    public function hintLine(array $answers): string
    {
        $expected = $this->hint ?? RuleHints::describe($this->declaredRules($answers));
        $marker = __($this->skippable ? 'tbe::forms.marker.optional' : 'tbe::forms.marker.required');

        return $expected === '' ? $marker : $marker.' · '.$expected;
    }

    /**
     * @param  array<string, ?string>  $answers
     * @return array<mixed>
     */
    protected function declaredRules(array $answers): array
    {
        $rules = $this->rules instanceof Closure ? ($this->rules)($answers) : ($this->rules ?? []);

        return array_values($rules);
    }

    /**
     * The declared rules plus whatever the step kind always requires.
     *
     * @param  array<string, ?string>  $answers
     * @return array<mixed>
     */
    protected function effectiveRules(array $answers): array
    {
        return ['required', ...$this->declaredRules($answers)];
    }

    /** @return array<string, string> */
    private function validationMessages(): array
    {
        $translations = trans('tbe::validation');
        $messages = [];

        foreach (is_array($translations) ? Arr::dot(Arr::except($translations, ['custom', 'attributes'])) : [] as $key => $message) {
            if (is_string($message)) {
                $messages[(string) $key] = $message;
            }
        }

        return $messages;
    }
}
