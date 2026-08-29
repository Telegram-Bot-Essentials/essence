<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Base for every `tbe:make:*` command.
 *
 * Laravel's GeneratorCommand assumes it is always scaffolding a consuming
 * app's own code (App\..., under app_path()) - true, and left alone, for
 * real usage (`php artisan tbe:make:*` in a bot project). But run via
 * `vendor/bin/testbench` from a package repo root (essence itself, or a
 * companion), there is no consuming app: the inherited rootNamespace()/
 * getPath() would instead write into whatever throwaway app Testbench
 * booted. Detect that runtime and target the package's own src/ tree -
 * resolved from its composer.json - instead.
 */
abstract class PackageGeneratorCommand extends GeneratorCommand
{
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
