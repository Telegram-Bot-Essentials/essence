<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Support\Str;
use TelegramBotEssentials\Essence\Enums\Roles;

/**
 * Shared behaviour for every `tbe:make:*` GeneratorCommand.
 *
 * These commands are meant to scaffold a *consuming app's* code
 * (App\Telegram\...) - that's the normal, real-world case and is left
 * untouched. But run via `vendor/bin/testbench` from a package repo root
 * (essence itself, or any companion), there is no consuming app: Laravel's
 * default rootNamespace()/getPath() would instead write into whatever
 * throwaway app Testbench booted. In that mode, target the package's own
 * src/ tree - read from its composer.json - instead.
 */
trait TgClassMaker
{
    private string $nameValue;

    private int $permValue;

    private string $permEnumValue;

    private string $typeValue;

    public function initializeName(): string
    {
        $name = trim($this->argument('name'));

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }

        $this->nameValue = preg_replace([
            '/answer/i',
            '/query/i',
            '/feature/i',
            '/callback/i',
            '/key/i',
            '/command/i',
        ], '', $name);

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
            if ($this->permValue == 100) {
                $arguments['--admin'] = true;
            }

            $this->call($command, $arguments);
        }
    }

    private function initializeValues(): void
    {
        $this->typeValue = strtoupper($this->nameValue);
        if ($this->hasOption('admin')) {
            $this->permValue = $this->option('admin') ? Roles::ADMIN->value : Roles::MEMBER->value;
            $this->permEnumValue = $this->option('admin') ? 'Roles::ADMIN->value' : 'Roles::MEMBER->value';
        }
    }

    protected function generateTextFromName(string $name): string
    {
        return ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)); // BuyService → Buy Service
    }

    protected function generateTranslationKeyFromName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)); // BuyService → buy_service
    }

    protected function rootNamespace(): string
    {
        return $this->packageRootNamespace() ?? parent::rootNamespace();
    }

    protected function getPath($name): string
    {
        if (! defined('TESTBENCH_CORE')) {
            return parent::getPath($name);
        }

        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return TESTBENCH_WORKING_PATH.'/src/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Under `vendor/bin/testbench`, resolve the package being developed
     * (cwd) to its own PSR-4 namespace, so generated classes land in its
     * src/ tree under its real namespace instead of a throwaway app's.
     * Returns null outside Testbench, or if composer.json has no `"src/"`
     * PSR-4 mapping - callers fall back to Laravel's normal rootNamespace().
     */
    private function packageRootNamespace(): ?string
    {
        if (! defined('TESTBENCH_CORE') || ! defined('TESTBENCH_WORKING_PATH')) {
            return null;
        }

        $composerJson = TESTBENCH_WORKING_PATH.'/composer.json';

        if (! is_file($composerJson)) {
            return null;
        }

        $contents = file_get_contents($composerJson);

        if ($contents === false) {
            return null;
        }

        $composer = json_decode($contents, true);
        $autoload = is_array($composer) ? ($composer['autoload'] ?? null) : null;
        $psr4 = is_array($autoload) ? ($autoload['psr-4'] ?? null) : null;

        if (! is_array($psr4)) {
            return null;
        }

        foreach ($psr4 as $namespace => $path) {
            if ($path === 'src/' && is_string($namespace)) {
                return Str::finish($namespace, '\\');
            }
        }

        return null;
    }
}
