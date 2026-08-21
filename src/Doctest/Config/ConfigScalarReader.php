<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

use function is_string;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;

/**
 * Reads scalar values from doctest.yaml mappings with contextual error messages.
 *
 * @visibility namespace
 */
final class ConfigScalarReader
{
    /**
     * Reads a required or defaulted non-empty string value.
     *
     * @param array<mixed> $data
     *
     * @throws DoctestException when the value is not a non-empty string
     */
    public function string(array $data, string $key, ?string $default, string $context): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new DoctestException(sprintf('Invalid doctest.yaml: "%s" must be a non-empty string.', $this->label($context, $key)));
        }

        return $value;
    }

    /**
     * Reads an optional non-empty string value, absent as null.
     *
     * @param array<mixed> $data
     *
     * @throws DoctestException when the value is present but not a non-empty string
     */
    public function optionalString(array $data, string $key, string $context): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        return $this->string($data, $key, null, $context);
    }

    /**
     * Returns the fully qualified key label used in error messages.
     */
    public function label(string $context, string $key): string
    {
        return $context === '' ? $key : $context . '.' . $key;
    }
}
