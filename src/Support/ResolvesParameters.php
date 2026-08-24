<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Support\Str;

trait ResolvesParameters
{
    /** Attempts to find a matching key from the provided list using flexible casing. */
    protected function findMatchingKey(array $keys, string $paramName): ?string
    {
        $keys = array_values(array_filter($keys, 'is_string'));
        if (empty($keys)) {
            return null;
        }

        foreach ($this->buildKeyCandidates($paramName) as $candidate) {
            if (in_array($candidate, $keys, true)) {
                return $candidate;
            }
        }

        $normalizedTargets = $this->buildNormalizedCandidates($paramName);
        if (empty($normalizedTargets)) {
            return null;
        }

        foreach ($keys as $key) {
            $normalizedKeyVariants = $this->buildNormalizedCandidates($key);
            if (! empty(array_intersect($normalizedTargets, $normalizedKeyVariants))) {
                return $key;
            }
        }

        return null;
    }

    /** Produces a list of key variants in different cases. */
    protected function buildKeyCandidates(string $name): array
    {
        $base = array_filter([
            $name,
            Str::snake($name),
            Str::camel($name),
            Str::studly($name),
            strtolower($name),
        ], fn ($value) => $value !== '');

        return array_values(array_unique($base));
    }

    /**
     * Creates normalized variants (snake_case + lower) for intersection checks.
     */
    protected function buildNormalizedCandidates(string $name): array
    {
        $variants = array_filter([
            Str::snake($name),
            strtolower($name),
        ], fn ($value) => $value !== '');

        return array_values(array_unique($variants));
    }
}
