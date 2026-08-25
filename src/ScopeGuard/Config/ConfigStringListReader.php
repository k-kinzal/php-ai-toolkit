<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Config;

use function is_array;
use function is_string;
use function sprintf;

use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * Reads string lists from scope.yaml mappings with contextual error messages.
 *
 * @visibility namespace
 */
final class ConfigStringListReader
{
    /**
     * Reads a list of non-empty strings, applying the default when absent.
     *
     * @template TValue
     * @param array<array-key, TValue> $data
     * @param list<string> $default
     * @return list<string>
     *
     * @throws ScopeGuardException when the value is not a list of non-empty strings
     */
    public function read(array $data, string $key, array $default, string $context): array
    {
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new ScopeGuardException(sprintf('Invalid scope.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new ScopeGuardException(sprintf('Invalid scope.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
            }
            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * Returns the fully qualified key label used in error messages.
     */
    public function label(string $context, string $key): string
    {
        return $context === '' ? $key : $context . '.' . $key;
    }
}
