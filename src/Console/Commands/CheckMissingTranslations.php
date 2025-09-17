<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckMissingTranslations extends Command
{
    protected $signature = 'translations:check {--base=en}';
    protected $description = 'Check for missing translation strings across languages';

    public function handle()
    {
        $langPath = lang_path();
        $this->info("Checking translations in $langPath");
        $baseLang = $this->option('base');

        if (!File::exists("$langPath/$baseLang")) {
            $this->error("Base language [$baseLang] not found in $langPath");
            return CommandAlias::FAILURE;
        }

        // Get all locales
        $locales = collect(File::directories($langPath))
            ->map(fn($dir) => basename($dir))
            ->filter(fn($locale) => $locale !== $baseLang && $locale !== 'vendor');

        $baseKeys = $this->loadLanguageKeys("$langPath/$baseLang");

        foreach ($locales as $locale) {
            $localeKeys = $this->loadLanguageKeys("$langPath/$locale");
            $missing = array_diff_key($baseKeys, $localeKeys);

            if (empty($missing)) {
                $this->info("✅ $locale: All keys present");
            } else {
                $this->warn("❌ $locale: Missing " . count($missing) . " keys");
                foreach (array_keys($missing) as $key) {
                    $this->line("   - $key");
                }
            }
        }

        return CommandAlias::SUCCESS;
    }

    /**
     * Load all translation keys from a locale directory
     */
    protected function loadLanguageKeys(string $path): array
    {
        $keys = [];

        foreach (File::allFiles($path) as $file) {
            $relativeKeyPrefix = basename($file, '.php');
            $content = File::getRequire($file);

            if (is_array($content)) {
                $keys = array_merge(
                    $keys,
                    $this->flattenKeys([$relativeKeyPrefix => $content])
                );
            }
        }

        return $keys;
    }

    /**
     * Flatten translation array into dot notation keys
     */
    protected function flattenKeys(array $array): array
    {
        return Arr::dot($array);
    }
}
