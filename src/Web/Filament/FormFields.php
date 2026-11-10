<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Step;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * A bot Form as panel fields, so a package that configures something through
 * a chat form (a prize, a claim method...) gets the same questions on the web
 * without writing them twice. Text steps become text inputs, Choice steps
 * selects; `when` and `dependsOn` hide fields and rebuild options as earlier
 * answers change. answers() then applies the form's own rules and casts.
 */
final class FormFields
{
    /** @return list<Field> */
    public static function for(Form $form): array
    {
        $engine = formEngine();
        $steps = $engine->steps($form);
        $state = function (Get $get) use ($steps): array {
            $answers = [];
            foreach (array_keys($steps) as $key) {
                $answers[$key] = $get($key);
            }

            return $answers;
        };

        $fields = [];
        foreach ($steps as $key => $step) {
            $context = fn (Get $get): array => $engine->contextOf($form, $step, $state($get));

            $field = $step instanceof Choice
                ? Select::make($key)
                    ->options(function (Get $get) use ($step, $context): array {
                        // A step that builds its options from earlier answers reads them here.
                        $step->isApplicable($context($get)[1]);

                        return $step->resolveOptions();
                    })
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => self::clearDependents($steps, $key, $set))
                : TextInput::make($key)->placeholder($step->getPlaceholder())->live(onBlur: true);

            $fields[] = $field
                ->label($engine->labelOf($form, $step))
                ->helperText($step->hintLine([]))
                ->required(! $step->isSkippable())
                ->visible(fn (Get $get): bool => $context($get)[0]);
        }

        return $fields;
    }

    /**
     * The form's answers, checked and cast as the chat would. Refuses an actor
     * the form's ability does not allow, as the chat does.
     *
     * @param  array<mixed>  $data  a panel form's state, by field
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function answers(Form $form, array $data): array
    {
        $ability = $form->getAbility();
        $actor = PanelActor::current();
        abort_unless($ability === null || ($actor instanceof BotUser && Gate::forUser($actor)->allows($ability)), 403);

        return formEngine()->answerAll($form, $data);
    }

    /**
     * answers() inside a panel action: a wrong answer is shown as a
     * notification and the action stays open; null then.
     *
     * @param  array<mixed>  $data  a panel form's state, by field
     * @return array<string, mixed>|null
     */
    public static function answersFor(Action $action, Form $form, array $data): ?array
    {
        try {
            return self::answers($form, $data);
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            $action->halt();

            return null;
        }
    }

    /** @param  array<string, Step>  $steps */
    private static function clearDependents(array $steps, string $changed, Set $set): void
    {
        foreach ($steps as $key => $step) {
            if (in_array($changed, $step->dependencies(), true)) {
                $set($key, null);
            }
        }
    }
}
