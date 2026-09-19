<?php

namespace TelegramBotEssentials\Essence\Forms\Steps;

use Closure;
use Illuminate\Validation\ValidationException;

/**
 * An answer picked from a set of options.
 *
 * A fixed array of options is shown as reply-keyboard buttons. Options given
 * as a closure are evaluated each time the prompt is built (they usually come
 * from the database) and shown as inline buttons with paging, since a reply
 * keyboard cannot hold a list of unknown length. inline() forces the inline
 * form for a fixed array too.
 */
class Choice extends Step
{
    /** @var array<int|string, string>|Closure(): array<int|string, string> */
    protected array|Closure $options = [];

    protected ?bool $inline = null;

    /** @var int<1, max> */
    protected int $columns = 2;

    /**
     * @param  array<int|string, string>|Closure(): array<int|string, string>  $options  value => label
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    public function isInline(): bool
    {
        return $this->inline ?? $this->options instanceof Closure;
    }

    /** @return int<1, max> */
    public function columnCount(): int
    {
        return $this->columns;
    }

    /** @return array<string, string> value => label */
    public function resolveOptions(): array
    {
        $options = $this->options instanceof Closure ? ($this->options)() : $this->options;

        $resolved = [];
        foreach ($options as $value => $label) {
            $resolved[(string) $value] = (string) $label;
        }

        return $resolved;
    }

    /** The value whose label is exactly $label, or null. */
    public function valueForLabel(string $label): ?string
    {
        $value = array_search($label, $this->resolveOptions(), true);

        return $value === false ? null : (string) $value;
    }

    public function displayAnswer(string $raw): string
    {
        return $this->resolveOptions()[$raw] ?? parent::displayAnswer($raw);
    }

    public function validate(string $value, array $answers, string $label): void
    {
        if (! array_key_exists($value, $this->resolveOptions())) {
            throw ValidationException::withMessages([$this->key => __('tbe::forms.errors.invalidChoice')]);
        }
    }

    public function hintLine(array $answers): string
    {
        return __($this->skippable ? 'tbe::forms.marker.optional' : 'tbe::forms.marker.required');
    }
}
