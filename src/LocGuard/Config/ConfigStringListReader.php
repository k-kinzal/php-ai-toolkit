<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use function array_values;
use function is_array;
use function is_string;
use function sprintf;

use Toolkit\LocGuard\LocGuardException;

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
     *
     * @throws LocGuardException when the value is not a list of non-empty strings
     */
    public function read(array $data, string $key, array $default, string $context = ''): array
    {
        $value = $data[$key] ?? $default;
        $label = $context === '' ? $key : $context . '.' . $key;
        if (!is_array($value) || array_values($value) !== $value) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $label));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $label));
            }
            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * Reads a required string list and optionally rejects an empty list.
     *
     * @param array<mixed> $data
     * @return list<string>
     *
     * @throws LocGuardException when the key is missing or the list is invalid
     */
    public function readRequired(array $data, string $key, string $context, bool $allowEmpty): array
    {
        if (!isset($data[$key])) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: "%s.%s" is required and must be a list of strings.',
                $context,
                $key,
            ));
        }

        $values = $this->read($data, $key, [], $context);
        if (!$allowEmpty && $values === []) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: "%s.%s" must contain at least one entry.',
                $context,
                $key,
            ));
        }

        return $values;
    }
}
