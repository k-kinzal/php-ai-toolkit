<?php

declare(strict_types=1);

namespace Guard\Config\Loc;

use function array_keys;

use Guard\Policy\PolicyException;

use function in_array;
use function sprintf;

/**
 * Rejects unsupported source metric configuration keys.
 */
final class ConfigKeyValidator
{
    /**
     * Ensures that every mapping key is explicitly supported.
     *
     * @param array<mixed> $data
     * @param list<string> $knownKeys
     *
     * @throws PolicyException when an unsupported key is present
     */
    public function rejectUnknown(array $data, array $knownKeys, string $context): void
    {
        foreach (array_keys($data) as $key) {
            if (!is_string($key) || !in_array($key, $knownKeys, true)) {
                throw new PolicyException(sprintf(
                    'Invalid loc.yaml: "%s" contains unsupported key "%s".',
                    $context,
                    (string) $key,
                ));
            }
        }
    }
}
