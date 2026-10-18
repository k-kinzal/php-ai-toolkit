<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

/**
 * Lays a partial rule definition over a complete one.
 *
 * Mappings merge key by key, so an override that sets only `decision.threshold`
 * keeps the imported instructions. Lists and scalars replace the imported
 * value, so an override that sets `violations` or `paths` writes the whole list.
 */
final class DocumentMerger
{
    /**
     * Returns the base with every key of the override applied.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    public function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $existing = $base[$key] ?? null;
            $base[$key] = is_array($value) && is_array($existing) && $this->isMapping($value) && $this->isMapping($existing)
                ? $this->merge($this->mapping($existing), $this->mapping($value))
                : $value;
        }

        return $base;
    }

    /**
     * Tells whether a non-empty array has string keys.
     *
     * @param array<array-key, mixed> $value
     */
    public function isMapping(array $value): bool
    {
        return $value !== [] && array_values($value) !== $value;
    }

    /**
     * Returns an array with its keys as strings.
     *
     * @param array<array-key, mixed> $value
     * @return array<string, mixed>
     */
    public function mapping(array $value): array
    {
        $mapping = [];
        foreach ($value as $key => $entry) {
            $mapping[(string) $key] = $entry;
        }

        return $mapping;
    }
}
