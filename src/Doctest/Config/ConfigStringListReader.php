<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

use function is_array;
use function is_string;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;

/**
 * Reads string lists from doctest.yaml mappings with contextual error messages.
 *
 * @visibility namespace
 */
final class ConfigStringListReader
{
    /**
     * Reads a list of non-empty strings, applying the default when absent.
     *
     * @param array<mixed> $data
     * @param list<string> $default
     * @return list<string>
     *
     * @throws DoctestException when the value is not a list of non-empty strings
     */
    public function read(array $data, string $key, array $default, string $context): array
    {
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new DoctestException(sprintf('Invalid doctest.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new DoctestException(sprintf('Invalid doctest.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
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
