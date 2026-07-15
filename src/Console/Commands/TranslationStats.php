<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;
use TelegramBotEssentials\Essence\Services\TranslationScanner;

class TranslationStats extends Command
{
    protected $signature = 'translations:stats {--base=} {--no-cache : Skip writing stats to cache}';

    protected $description = 'Compute translation completion stats across app and TBE package lang files';

    public function handle(TranslationScanner $scanner): int
    {
        if ($this->option('base')) {
            config(['tbe-essence.translation_stats.base_locale' => $this->option('base')]);
        }

        $baseLocale = $scanner->baseLocale();
        $baseKeys = $scanner->baseKeys();
        $total = count($baseKeys);
        $stats = $scanner->computeStats();

        $this->info("Base locale: {$baseLocale} ({$total} keys)");

        foreach ($stats as $locale => $localeStats) {
            $this->line(sprintf(
                '%s %d/%d (%d%%)',
                $locale,
                $localeStats['translated'],
                $localeStats['total'],
                $localeStats['percent']
            ));
        }

        $payload = [
            'base' => $baseLocale,
            'total' => $total,
            'locales' => $stats,
            'computed_at' => now()->toIso8601String(),
        ];

        if (! $this->option('no-cache')) {
            Cache::put(
                config('tbe-essence.translation_stats.cache_key'),
                $payload,
                config('tbe-essence.translation_stats.ttl')
            );
            $this->info('Cached translation stats.');
        }

        return CommandAlias::SUCCESS;
    }
}
