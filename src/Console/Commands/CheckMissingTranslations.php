<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use TelegramBotEssentials\Essence\Services\TranslationScanner;

class CheckMissingTranslations extends Command
{
    protected $signature = 'translations:check {--base=}';

    protected $description = 'Check for missing translation strings across languages';

    public function handle(TranslationScanner $scanner): int
    {
        if ($this->option('base')) {
            config(['tbe-essence.translation_stats.base_locale' => $this->option('base')]);
        }

        $baseLocale = $scanner->baseLocale();
        $this->info('Checking translations with base locale: ' . $baseLocale);

        $baseKeys = $scanner->baseKeys();
        $hadIssues = false;

        foreach ($scanner->discoverLocales() as $locale) {
            if ($locale === $baseLocale) {
                continue;
            }

            $localeKeys = $scanner->loadKeysForLocale($locale);
            $missing = array_diff_key($baseKeys, $localeKeys);

            if (empty($missing)) {
                $this->info("✅ $locale: All keys present");
                continue;
            }

            $hadIssues = true;
            $this->warn('❌ ' . $locale . ': Missing ' . count($missing) . ' keys');
            foreach (array_keys($missing) as $key) {
                $this->line('   - ' . $key);
            }
        }

        return $hadIssues ? CommandAlias::FAILURE : CommandAlias::SUCCESS;
    }
}
