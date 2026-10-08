<?php

declare(strict_types=1);

namespace Guard\Input;

/**
 * The collector's project-wide boundary, intersected with every policy input.
 * @property-read list<string> $include
 * @property-read list<string> $exclude
 */
final class Scope
{
    /**
     * @param list<string> $include relative paths or segment-aware globs
     * @param list<string> $exclude paths or globs whose matching subtrees are excluded
     */
    public function __construct(private array $include = ['**'], private array $exclude = [])
    {
    }

    /**
     * Returns the immutable collection boundary.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'include' => $this->include,
            'exclude' => $this->exclude,
            default => null,
        };
    }
}
