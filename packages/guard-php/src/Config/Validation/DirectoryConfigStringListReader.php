<?php

declare(strict_types=1);

namespace Guard\Config\Validation;

use function array_key_exists;

use Guard\Diagnostic\PolicyException;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Reads string lists from tree.yaml mappings with contextual error messages.
 */
final class DirectoryConfigStringListReader
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
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid tree.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new PolicyException(sprintf('Invalid tree.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
            }
            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * Reads an optional list of non-empty strings, returning null when the key is absent.
     *
     * @param array<mixed> $data
     * @return ?list<string>
     *
     * @throws PolicyException when the value is not a list of non-empty strings
     */
    public function readOptional(array $data, string $key, string $context): ?array
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        if ($data[$key] === null) {
            throw new PolicyException(sprintf('Invalid tree.yaml: "%s" must be a list of strings.', $this->label($context, $key)));
        }

        return $this->read($data, $key, [], $context);
    }

    /**
     * Returns the fully qualified key label used in error messages.
     */
    public function label(string $context, string $key): string
    {
        return $context === '' ? $key : $context . '.' . $key;
    }
}
