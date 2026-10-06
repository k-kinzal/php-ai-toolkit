<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Policy\PolicyException;

/**
 * Validates configuration shapes without silently dropping misspelled keys.
 */
final class Schema
{
    /**
     * @param mixed $value
     * @param list<string> $keys
     * @return array<string, mixed>
     * @throws PolicyException when a mapping contains unknown keys
     */
    public function mapping($value, array $keys, string $context): array
    {
        if (!is_array($value) || ($value !== [] && array_values($value) === $value)) {
            throw new PolicyException($context . ' must be a mapping.');
        }
        foreach ($value as $key => $entry) {
            if (!is_string($key) || !in_array($key, $keys, true)) {
                throw new PolicyException($context . ': unknown key "' . $key . '". Use: ' . implode(', ', $keys) . '.');
            }
        }
        return $value;
    }
    /**
     * @param mixed $value
     * @return list<string>
     * @throws PolicyException when a path list is invalid
     */
    public function strings($value, string $context): array
    {
        if (!is_array($value) || array_values($value) !== $value) {
            throw new PolicyException($context . ' must be a list of strings.');
        }
        $result = [];
        foreach ($value as $entry) {
            $result[] = $this->string($entry, $context);
        }
        return $result;
    }
    /**
     * @param mixed $value
     * @throws PolicyException when a required string is missing
     */
    public function string($value, string $context): string
    {
        if (!is_string($value) || $value === '') {
            throw new PolicyException($context . ' must be a non-empty string.');
        }
        return $value;
    }
}
