<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Finds configured forbidden suffixes on class-like names.
 */
final class ForbiddenClassLikeSuffixes
{
    /** @var list<string> */
    private array $suffixes;

    /**
     * @param list<string> $suffixes configured forbidden suffixes
     */
    public function __construct(array $suffixes)
    {
        $this->suffixes = array_values(array_unique(array_filter(
            $suffixes,
            static fn (string $suffix): bool => $suffix !== '',
        )));
    }

    /**
     * Returns the matching forbidden suffix for a class-like name.
     */
    public function matchingSuffix(string $name): ?string
    {
        foreach ($this->suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return $suffix;
            }
        }

        return null;
    }
}
