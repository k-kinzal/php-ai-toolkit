<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

use function is_string;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;

/**
 * Reads scalar values from parsed doc.yaml data.
 */
final class ConfigScalarReader
{
    /**
     * Reads a required non-empty string value with a default fallback.
     *
     * @param array<array-key, mixed> $data
     *
     * @throws DocGenException when the value is present but not a non-empty string
     */
    public function string(array $data, string $key, string $default): string
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];
        if (!is_string($value) || $value === '') {
            throw new DocGenException(sprintf('Invalid doc.yaml: "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /**
     * Reads an optional non-empty string value, returning null when absent.
     *
     * @param array<array-key, mixed> $data
     *
     * @throws DocGenException when the value is present but not a non-empty string
     */
    public function optionalString(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];
        if (!is_string($value) || $value === '') {
            throw new DocGenException(sprintf('Invalid doc.yaml: "%s" must be a non-empty string.', $key));
        }

        return $value;
    }
}
