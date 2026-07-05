<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function is_array;
use function is_string;

use PhpAiToolkit\LocGuard\LocGuardException;

use function sprintf;

/**
 * Reads non-empty string lists from loc.yaml mappings.
 */
final class ConfigStringListReader
{
    /**
     * Reads a list of strings from the given key.
     *
     * @param array<mixed> $data
     * @param list<string> $default
     * @return list<string>
     */
    public function read(array $data, string $key, array $default): array
    {
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $key));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $key));
            }
            $strings[] = $entry;
        }

        return $strings;
    }
}
