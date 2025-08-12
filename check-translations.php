#!/usr/bin/env php
<?php

// Path to your package's lang directory
$langDir = __DIR__ . '/lang';
$base    = $argv[1] ?? 'en'; // pass base locale as first argument, default 'en'

if (!is_dir($langDir)) {
    fwrite(STDERR, "❌ Lang directory not found: $langDir\n");
    exit(1);
}

$basePath = "$langDir/$base";
if (!is_dir($basePath)) {
    fwrite(STDERR, "❌ Base locale [$base] not found at: $basePath\n");
    exit(1);
}

$baseKeys = loadLocaleKeys($basePath);

// Find all other locales
$locales = array_values(array_filter(
    array_map('basename', glob($langDir . '/*', GLOB_ONLYDIR)),
    fn($loc) => $loc !== $base
));

$hadIssues = false;

foreach ($locales as $locale) {
    $localeKeys = loadLocaleKeys("$langDir/$locale");

    $missing = array_diff_key($baseKeys, $localeKeys);
    $extra   = array_diff_key($localeKeys, $baseKeys);

    if (!$missing && !$extra) {
        echo "✅ $locale: OK\n";
        continue;
    }

    $hadIssues = true;
    echo "❌ $locale:\n";
    if ($missing) {
        echo "  Missing:\n";
        foreach (array_keys($missing) as $key) {
            echo "    - $key\n";
        }
    }
    if ($extra) {
        echo "  Extra (not in base):\n";
        foreach (array_keys($extra) as $key) {
            echo "    - $key\n";
        }
    }
}

// Optional: check JSON translation files
checkJsonFiles($langDir, $base);

exit($hadIssues ? 1 : 0);


// ----------------------
// Helper functions
// ----------------------
function loadLocaleKeys(string $localePath): array
{
    $keys = [];
    foreach (glob($localePath . '/*.php') as $file) {
        $prefix  = pathinfo($file, PATHINFO_FILENAME);
        $content = @include $file;
        if (is_array($content)) {
            $keys = array_merge($keys, arrDot([$prefix => $content]));
        }
    }
    return $keys;
}

function arrDot(array $array, string $prepend = ''): array
{
    $results = [];
    foreach ($array as $key => $value) {
        $newKey = $prepend . $key;
        if (is_array($value)) {
            $results += arrDot($value, $newKey . '.');
        } else {
            $results[$newKey] = $value;
        }
    }
    return $results;
}

function checkJsonFiles(string $langDir, string $base): void
{
    $baseJson = "$langDir/$base.json";
    if (!is_file($baseJson)) return;

    $baseArr = json_decode(file_get_contents($baseJson), true) ?: [];

    foreach (glob($langDir . '/*.json') as $jsonPath) {
        $locale = basename($jsonPath, '.json');
        if ($locale === $base) continue;

        $arr = json_decode(file_get_contents($jsonPath), true) ?: [];
        $missing = array_diff_key($baseArr, $arr);
        $extra   = array_diff_key($arr, $baseArr);

        if (!$missing && !$extra) {
            echo "✅ $locale.json: OK\n";
            continue;
        }

        echo "❌ $locale.json:\n";
        if ($missing) {
            echo "  Missing (JSON):\n";
            foreach (array_keys($missing) as $key) {
                echo "    - $key\n";
            }
        }
        if ($extra) {
            echo "  Extra (JSON):\n";
            foreach (array_keys($extra) as $key) {
                echo "    - $key\n";
            }
        }
    }
}
