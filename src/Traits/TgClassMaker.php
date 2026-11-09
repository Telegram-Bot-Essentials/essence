<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Support\Str;

/**
 * Name-parsing and text-generation helpers shared by every `tbe:make:*`
 * command. Path/namespace resolution lives in PackageGeneratorCommand,
 * which every such command extends - not here.
 */
trait TgClassMaker
{
    private string $nameValue;

    /** Sub-folders given with the name ("Shop/" for Shop/BuyKey), kept as typed. */
    private string $namePrefix = '';

    private ?string $abilityValue = null;

    private string $typeValue;

    public function initializeName(): string
    {
        $name = trim($this->argument('name'));

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }

        $name = str_replace('\\', '/', $name);
        $slash = strrpos($name, '/');
        $this->namePrefix = $slash === false ? '' : Str::substr($name, 0, $slash + 1);
        $name = $slash === false ? $name : Str::substr($name, $slash + 1);

        $stripped = preg_replace([
            '/answer/i',
            '/query/i',
            '/feature/i',
            '/callback/i',
            '/key/i',
            '/command/i',
        ], '', $name);

        $this->nameValue = is_string($stripped) ? $stripped : $name;

        return $this->namePrefix.$this->nameValue;
    }

    private function handleOptions(): void
    {
        // Raw options: only the feature command defines --all and the type flags.
        $options = $this->input->getOptions();
        $types = [];
        if (($options['all'] ?? false) === true) {
            $types = array_keys($this->map);
        } else {
            foreach ($this->map as $option => $command) {
                if (($options[$option] ?? false) === true) {
                    $types[] = $option;
                }
            }
        }

        foreach ($types as $type) {
            $command = $this->map[$type];

            $arguments = [
                'name' => $this->namePrefix.$this->nameValue,
            ];
            if ($this->abilityValue !== null) {
                $arguments['--ability'] = $this->abilityValue;
            }

            $this->call($command, $arguments);
        }
    }

    private function initializeValues(): void
    {
        $this->typeValue = strtoupper($this->nameValue);
        $this->abilityValue = $this->resolveAbility();
    }

    /** --ability when given; none leaves the handler open to everyone. */
    private function resolveAbility(): ?string
    {
        // Raw options rather than option(): not every command using this trait
        // defines it.
        $ability = $this->input->getOptions()['ability'] ?? null;

        return is_string($ability) && $ability !== '' ? $ability : null;
    }

    /** The generated `$ability` value as PHP source. */
    private function abilityLiteral(): string
    {
        return $this->abilityValue === null ? 'null' : var_export($this->abilityValue, true);
    }

    protected function generateTextFromName(string $name): string
    {
        return ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)); // BuyService → Buy Service
    }

    protected function generateTranslationKeyFromName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)); // BuyService → buy_service
    }
}
