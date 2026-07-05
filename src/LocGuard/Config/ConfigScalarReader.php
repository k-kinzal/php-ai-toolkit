<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function is_int;
use function is_string;

use PhpAiToolkit\LocGuard\LocGuardException;

use function sprintf;

/**
 * Reads scalar values from loc.yaml mappings.
 */
final class ConfigScalarReader
{
    /**
     * Reads a non-empty string value.
     *
     * @param array<mixed> $data
     */
    public function string(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /**
     * Reads a positive integer value.
     *
     * @param array<mixed> $data
     */
    public function positiveInt(array $data, string $key, int $default): int
    {
        $value = $data[$key] ?? $default;
        if (!is_int($value) || $value < 1) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "limits.%s" must be a positive integer.', $key));
        }

        return $value;
    }
}
