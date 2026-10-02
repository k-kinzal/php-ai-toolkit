<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Config;

use function array_key_exists;
use function implode;
use function in_array;
use function is_bool;
use function is_int;
use function is_string;
use function sprintf;

use Toolkit\TreeGuard\TreeGuardException;

/**
 * Reads scalar values from tree.yaml mappings with contextual error messages.
 */
final class ConfigScalarReader
{
    /** @var list<string> */
    private const CASES = ['pascal', 'camel', 'snake', 'kebab'];

    /**
     * Reads a required or defaulted non-empty string value.
     *
     * @param array<mixed> $data
     *
     * @throws TreeGuardException when the value is not a non-empty string
     */
    public function string(array $data, string $key, ?string $default, string $context): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" must be a non-empty string.', $this->label($context, $key)));
        }

        return $value;
    }

    /**
     * Reads a boolean value with a default.
     *
     * @param array<mixed> $data
     *
     * @throws TreeGuardException when the value is not a boolean
     */
    public function bool(array $data, string $key, bool $default, string $context): bool
    {
        $value = $data[$key] ?? $default;
        if (!is_bool($value)) {
            throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" must be a boolean.', $this->label($context, $key)));
        }

        return $value;
    }

    /**
     * Reads an optional positive integer value, returning null when absent.
     *
     * @param array<mixed> $data
     *
     * @throws TreeGuardException when the value is not a positive integer
     */
    public function optionalPositiveInt(array $data, string $key, string $context): ?int
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];
        if (!is_int($value) || $value < 1) {
            throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" must be a positive integer.', $this->label($context, $key)));
        }

        return $value;
    }

    /**
     * Reads an optional naming convention value, returning null when absent.
     *
     * @param array<mixed> $data
     *
     * @throws TreeGuardException when the value is not a supported naming convention
     */
    public function optionalCase(array $data, string $key, string $context): ?string
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];
        if (!is_string($value) || !in_array($value, self::CASES, true)) {
            throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" must be one of: %s.', $this->label($context, $key), implode(', ', self::CASES)));
        }

        return $value;
    }

    /**
     * Returns the fully qualified key label used in error messages.
     */
    public function label(string $context, string $key): string
    {
        return $context === '' ? $key : $context . '.' . $key;
    }
}
