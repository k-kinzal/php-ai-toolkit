<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Config;

use function is_string;

use PhpAiToolkit\ScopeGuard\ScopeGuardException;

use function sprintf;

/**
 * Reads scalar values from scope.yaml mappings with contextual error messages.
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
     * @throws ScopeGuardException when the value is not a non-empty string
     */
    public function string(array $data, string $key, ?string $default, string $context): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new ScopeGuardException(sprintf('Invalid scope.yaml: "%s" must be a non-empty string.', $this->label($context, $key)));
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
