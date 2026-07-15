<?php

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationScanner
{
    public function baseLocale(): string
    {
        return config('tbe-essence.translation_stats.base_locale', 'en');
    }

    /**
     * @return array<string, string>
     */
    public function baseKeys(): array
    {
        return $this->loadKeysForLocale($this->baseLocale());
    }

    /**
     * @return array<string, array{translated: int, total: int, percent: int}>
     */
    public function computeStats(): array
    {
        $baseKeys = $this->baseKeys();
        $total = count($baseKeys);
        $stats = [];

        foreach ($this->discoverLocales() as $locale) {
            if ($locale === $this->baseLocale()) {
                continue;
            }

            $localeKeys = $this->loadKeysForLocale($locale);
            $translated = count(array_intersect_key($baseKeys, $localeKeys));
            $stats[$locale] = [
                'translated' => $translated,
                'total' => $total,
                'percent' => $total > 0 ? (int) round(($translated / $total) * 100) : 100,
            ];
        }

        return $stats;
    }

    /**
     * @return list<string>
     */
    public function discoverAppLocales(): array
    {
        $langPath = lang_path();

        if (! File::isDirectory($langPath)) {
            return [];
        }

        return collect(File::directories($langPath))
            ->map(fn (string $dir) => basename($dir))
            ->filter(fn (string $locale) => $locale !== 'vendor')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function discoverPackageLocales(): array
    {
        $locales = [];

        foreach ($this->packageLangRoots() as $langRoot) {
            if (! File::isDirectory($langRoot)) {
                continue;
            }

            foreach (File::directories($langRoot) as $dir) {
                $locales[] = basename($dir);
            }
        }

        return array_values(array_unique($locales));
    }

    /**
     * Locales present in app lang/ and at least one TBE package lang dir.
     *
     * @return list<string>
     */
    public function discoverSupportedLocales(): array
    {
        $appLocales = $this->discoverAppLocales();
        $packageLocales = $this->discoverPackageLocales();

        return collect($appLocales)
            ->filter(fn (string $locale) => in_array($locale, $packageLocales, true))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function discoverLocales(): array
    {
        return collect($this->discoverAppLocales())
            ->merge($this->discoverPackageLocales())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function loadKeysForLocale(string $locale): array
    {
        $keys = [];

        foreach ($this->localeSources($locale) as $source) {
            $keys = array_merge($keys, $this->loadKeysFromPath($source['path'], $source['prefix']));
        }

        return $keys;
    }

    /**
     * @return list<array{path: string, prefix: string}>
     */
    protected function localeSources(string $locale): array
    {
        $sources = [];
        $appLocalePath = lang_path($locale);

        if (File::isDirectory($appLocalePath)) {
            $sources[] = ['path' => $appLocalePath, 'prefix' => 'app'];
        }

        $publishedVendorPath = lang_path("vendor");
        if (File::isDirectory($publishedVendorPath)) {
            foreach (File::directories($publishedVendorPath) as $vendorNamespaceDir) {
                $localePath = $vendorNamespaceDir . DIRECTORY_SEPARATOR . $locale;
                if (File::isDirectory($localePath)) {
                    $sources[] = [
                        'path' => $localePath,
                        'prefix' => 'vendor.' . basename($vendorNamespaceDir),
                    ];
                }
            }
        }

        foreach ($this->packageLangRoots() as $packageName => $langRoot) {
            $localePath = $langRoot . DIRECTORY_SEPARATOR . $locale;
            if (File::isDirectory($localePath)) {
                $sources[] = ['path' => $localePath, 'prefix' => $packageName];
            }
        }

        return $sources;
    }

    /**
     * @return array<string, string>
     */
    protected function loadKeysFromPath(string $path, string $prefix): array
    {
        $keys = [];

        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePrefix = $prefix . '.' . $file->getBasename('.php');
            $content = File::getRequire($file->getPathname());

            if (is_array($content)) {
                $keys = array_merge($keys, $this->flattenKeys([$relativePrefix => $content]));
            }
        }

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    protected function flattenKeys(array $array): array
    {
        return Arr::dot($array);
    }

    /**
     * @return array<string, string>
     */
    protected function packageLangRoots(): array
    {
        $roots = [];
        $vendorPath = base_path('vendor/telegram-bot-essentials');

        if (! File::isDirectory($vendorPath)) {
            return $roots;
        }

        foreach (File::directories($vendorPath) as $packageDir) {
            $langRoot = $packageDir . DIRECTORY_SEPARATOR . 'lang';
            if (File::isDirectory($langRoot)) {
                $roots[basename($packageDir)] = $langRoot;
            }
        }

        return $roots;
    }
}
