<?php

declare(strict_types=1);

namespace Guard\Config\Validation;

use Guard\Policy\PolicyException;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Reads string lists from doc-guard.yaml mappings with contextual error messages.
 */
final class HeadingConfigStringListReader
{
    /**
     * Reads a list of non-empty strings, applying the default when absent.
     *
     * @param array<mixed> $data
     * @param list<string> $default
     * @return list<string>
     *
     * @throws PolicyException when the value is not a list of non-empty strings
     */
    public function read(array $data, string $key, array $default, string $context): array
    {
        $label = $context === '' ? $key : $context . '.' . $key;
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a list of strings.', $label));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a list of strings.', $label));
            }
            $strings[] = $entry;
        }

        return $strings;
    }
}
