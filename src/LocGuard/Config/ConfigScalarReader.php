<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use function is_int;
use function is_string;
use function sprintf;

use Toolkit\LocGuard\LocGuardException;

/**
 * Reads scalar values from loc.yaml mappings.
 */
final class ConfigScalarReader
{
    /**
     * Reads a non-empty string value.
     *
     * @param array<mixed> $data
     *
     * @throws LocGuardException when the value is not a non-empty string
     */
    public function string(array $data, string $key, string $default, string $context = ''): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: "%s" must be a non-empty string.',
                $context === '' ? $key : $context . '.' . $key,
            ));
        }

        return $value;
    }

    /**
     * Reads a required non-empty string value.
     *
     * @param array<mixed> $data
     *
     * @throws LocGuardException when the key is missing or its value is not a non-empty string
     */
    public function requiredString(array $data, string $key, string $context): string
    {
        if (!isset($data[$key])) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: "%s.%s" is required and must be a non-empty string.',
                $context,
                $key,
            ));
        }

        return $this->string($data, $key, '', $context);
    }

    /**
     * Reads an optional non-empty string value.
     *
     * @param array<mixed> $data
     *
     * @throws LocGuardException when the configured value is not a non-empty string
     */
    public function optionalString(array $data, string $key, string $context): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        return $this->string($data, $key, '', $context);
    }

    /**
     * Reads a positive integer or an explicit null that disables a metric.
     *
     * @param array<mixed> $data
     *
     * @throws LocGuardException when the value is neither null nor a positive integer
     */
    public function nullablePositiveInt(array $data, string $key, string $context): ?int
    {
        $value = $data[$key];
        if ($value !== null && (!is_int($value) || $value < 1)) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: "%s.%s" must be a positive integer or null.',
                $context,
                $key,
            ));
        }

        return $value;
    }
}
