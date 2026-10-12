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

    private ?string $permissionValue = null;

    private string $typeValue;

    public function initializeName(): string
    {
        $argument = $this->argument('name');
        $name = trim(is_string($argument) ? $argument : '');

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }

        $stripped = preg_replace([
            '/answer/i',
            '/query/i',
            '/feature/i',
            '/callback/i',
            '/key/i',
            '/command/i',
        ], '', $name);

        $this->nameValue = is_string($stripped) ? $stripped : $name;

        return $this->nameValue;
    }

    private function handleOptions(): void
    {
        $types = [];
        if ($this->option('all')) {
            $types = array_keys($this->map);
        } else {
            foreach ($this->map as $option => $command) {
                if ($this->option($option)) {
                    $types[] = $option;
                }
            }
        }

        foreach ($types as $type) {
            $command = $this->map[$type];

            $arguments = [
                'name' => $this->nameValue,
            ];
            if (($this->input->getOptions()['admin'] ?? false) === true) {
                $arguments['--admin'] = true;
            }
            if ($this->permissionValue !== null) {
                $arguments['--permission'] = $this->permissionValue;
            }

            $this->call($command, $arguments);
        }
    }

    private function initializeValues(): void
    {
        $this->typeValue = strtoupper($this->nameValue);
        $this->permissionValue = $this->resolvePermission();
    }

    /**
     * --permission when given; with --admin alone, "app.<snake_name>", which
     * the app still has to register with permissionRegistry().
     */
    private function resolvePermission(): ?string
    {
        // Raw options rather than option(): not every command using this trait
        // defines both.
        $options = $this->input->getOptions();
        $permission = $options['permission'] ?? null;

        if (is_string($permission) && $permission !== '') {
            return $permission;
        }

        if (($options['admin'] ?? false) === true) {
            return 'app.'.$this->generateTranslationKeyFromName($this->nameValue);
        }

        return null;
    }

    /** The generated `$permission` value as PHP source. */
    private function permissionLiteral(): string
    {
        return $this->permissionValue === null ? 'null' : var_export($this->permissionValue, true);
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
