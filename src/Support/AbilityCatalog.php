<?php

namespace TelegramBotEssentials\Essence\Support;

use Closure;
use Illuminate\Support\Facades\Lang;
use TelegramBotEssentials\Essence\Contracts\DeclaresAbility;

/**
 * Every Gate ability the bot's handlers ask for, so a package that hands
 * abilities out can list them without each one being written down twice.
 *
 * The handlers are the source: whatever the buses and the form registry
 * hold is read when the catalog is asked, so a handler registered late
 * still shows up. declare() adds an ability no handler names, one that is
 * only checked in code with hasAccess().
 *
 * Labels come from an `abilities` translation file, keyed by the full
 * ability name: the app's own lang/<locale>/abilities.php first, then each
 * package's (loadTranslationsFrom namespace) in turn. A label given to
 * declare() wins over both; with none, the label is the name itself.
 */
class AbilityCatalog
{
    /** @var array<string, Closure(): string|string|null> */
    private array $declared = [];

    /**
     * @param  Closure(): string|string|null  $label  a translation key, a closure, or null for the abilities file
     */
    public function declare(string $ability, Closure|string|null $label = null): self
    {
        $this->declared[$ability] = $label ?? ($this->declared[$ability] ?? null);

        return $this;
    }

    /**
     * Every known ability, sorted by name.
     *
     * @return list<string>
     */
    public function all(): array
    {
        $abilities = array_keys($this->declared);

        foreach ($this->handlers() as $handler) {
            if ($handler instanceof DeclaresAbility && ($ability = $handler->getAbility()) !== null) {
                $abilities[] = $ability;
            }
        }

        $abilities = array_values(array_unique($abilities));
        sort($abilities);

        return $abilities;
    }

    public function has(string $ability): bool
    {
        return in_array($ability, $this->all(), true);
    }

    /**
     * The abilities grouped by their first segment ("billing" for
     * "billing.offers.manage"), groups and names sorted.
     *
     * @return array<string, list<string>>
     */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->all() as $ability) {
            $groups[explode('.', $ability, 2)[0]][] = $ability;
        }
        ksort($groups);

        return $groups;
    }

    /** The ability's label in the current locale. */
    public function label(string $ability): string
    {
        $declared = $this->declared[$ability] ?? null;

        if ($declared instanceof Closure) {
            return $declared();
        }

        if (is_string($declared)) {
            $translated = __($declared);

            return is_string($translated) ? $translated : $declared;
        }

        foreach ($this->labelSources() as $file) {
            // The whole file is read and indexed by the literal name: a
            // dotted ability inside a translation key would be read as
            // nested arrays.
            $lines = Lang::get($file);

            if (is_array($lines) && is_string($lines[$ability] ?? null)) {
                return $lines[$ability];
            }
        }

        return $ability;
    }

    /** @return iterable<mixed> */
    private function handlers(): iterable
    {
        yield from replyKeyBus()->getReplyKeys();
        yield from callbackQueryBus()->getCallbackQueryTypes();
        yield from stateAnswerBus()->getStateAnswerTypes();
        yield from commandBus()->getCommands();
        yield from textMatcherBus()->getTextMatchers();
        yield from formRegistry()->all();

        if (($inline = inlineQueryBus()->getHandler()) !== null) {
            yield $inline;
        }
    }

    /** @return list<string> */
    private function labelSources(): array
    {
        $sources = ['abilities'];

        foreach (array_keys(app('translator')->getLoader()->namespaces()) as $namespace) {
            $sources[] = "{$namespace}::abilities";
        }

        return $sources;
    }
}
