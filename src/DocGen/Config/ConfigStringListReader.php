<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

use function array_values;
use function is_array;
use function is_string;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;

/**
 * Reads string list values from parsed doc.yaml data.
 */
final class ConfigStringListReader
{
    /**
     * Reads a list of non-empty strings with a default fallback.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $default
     *
     * @return list<string>
     *
     * @throws DocGenException when the value is not a list of non-empty strings
     */
    public function read(array $data, string $key, array $default): array
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];
        if (!is_array($value) || array_values($value) !== $value) {
            throw new DocGenException(sprintf('Invalid doc.yaml: "%s" must be a list of strings.', $key));
        }

        $strings = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new DocGenException(sprintf('Invalid doc.yaml: "%s" must contain only non-empty strings.', $key));
            }

            $strings[] = $item;
        }

        return $strings;
    }
}
