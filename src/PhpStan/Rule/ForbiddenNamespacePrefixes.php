<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Finds forbidden namespace prefixes that match a namespace.
 */
final class ForbiddenNamespacePrefixes
{
    /** @var list<string> */
    private array $prefixes;

    /**
     * @param list<string> $prefixes configured forbidden prefixes
     */
    public function __construct(array $prefixes)
    {
        $normalizer = new NamespacePrefixNormalizer();
        $this->prefixes = array_values(array_unique(array_filter(
            array_map([$normalizer, 'normalize'], $prefixes),
            static fn (string $prefix): bool => $prefix !== '',
        )));
    }

    /**
     * Returns the forbidden prefix matching the namespace.
     */
    public function matchingPrefix(string $namespace): ?string
    {
        foreach ($this->prefixes as $prefix) {
            if ($namespace === $prefix || str_starts_with($namespace, $prefix . '\\')) {
                return $prefix;
            }
        }

        return null;
    }
}
