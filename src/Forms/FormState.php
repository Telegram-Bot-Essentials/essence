<?php

namespace TelegramBotEssentials\Essence\Forms;

/**
 * Where a user is in a running form, as it is stored in their state.
 *
 * `answers` maps step key to the raw answer (null for a skipped step) and
 * only ever gains or overwrites entries: which of them count is decided when
 * reading (see FormEngine::walk), so flipping an earlier answer back restores
 * later ones. `msgs` remembers the Telegram message ids of each step's prompt
 * (`p`) and, for inline choices, its options message (`o`) so they can be
 * edited later.
 */
final class FormState
{
    public const TYPE = 'FORM';

    public const METHOD = 'answer';

    /** The step key while the summary/confirm screen is showing. */
    public const CONFIRM = '__confirm';

    /**
     * @param  array<string, ?string>  $answers
     * @param  array<string, array{p: int, o?: int}>  $msgs
     * @param  array<string, mixed>  $ctx
     */
    public function __construct(
        public string $form,
        public string $step,
        public array $answers = [],
        public array $msgs = [],
        public ?int $meta = null,
        public array $ctx = [],
        public int $at = 0,
    ) {}

    /**
     * Rebuilds the state from stored params, dropping anything malformed
     * rather than trusting it: the params round-trip through a text column.
     *
     * @param  array<mixed>  $params
     */
    public static function fromParams(array $params): ?self
    {
        if (! is_string($params['form'] ?? null) || ! is_string($params['step'] ?? null)) {
            return null;
        }

        $answers = [];
        foreach (is_array($params['answers'] ?? null) ? $params['answers'] : [] as $key => $answer) {
            if (is_string($answer) || $answer === null) {
                $answers[(string) $key] = $answer;
            }
        }

        $msgs = [];
        foreach (is_array($params['msgs'] ?? null) ? $params['msgs'] : [] as $key => $ids) {
            if (is_array($ids) && is_numeric($ids['p'] ?? null)) {
                $msgs[(string) $key] = ['p' => (int) $ids['p']];

                if (is_numeric($ids['o'] ?? null)) {
                    $msgs[(string) $key]['o'] = (int) $ids['o'];
                }
            }
        }

        $ctx = is_array($params['ctx'] ?? null) ? array_filter($params['ctx'], 'is_string', ARRAY_FILTER_USE_KEY) : [];

        return new self(
            form: $params['form'],
            step: $params['step'],
            answers: $answers,
            msgs: $msgs,
            meta: is_numeric($params['meta'] ?? null) ? (int) $params['meta'] : null,
            ctx: $ctx,
            at: is_numeric($params['at'] ?? null) ? (int) $params['at'] : 0,
        );
    }

    /** The state of the given user string, or null when it is not a form's. */
    public static function fromStateString(?string $state): ?self
    {
        $decoded = decodeAnswerState($state);

        return $decoded['type'] === self::TYPE ? self::fromParams($decoded['params']) : null;
    }

    /** @return array<string, mixed> */
    public function toParams(): array
    {
        return [
            'form' => $this->form,
            'step' => $this->step,
            'answers' => $this->answers,
            'msgs' => $this->msgs,
            'meta' => $this->meta,
            'ctx' => $this->ctx,
            'at' => $this->at,
        ];
    }

    public function toStateString(): string
    {
        return encodeAnswerState(self::TYPE, self::METHOD, $this->toParams());
    }
}
