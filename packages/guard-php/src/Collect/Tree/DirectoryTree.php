<?php

declare(strict_types=1);

namespace Guard\Collect\Tree;

/**
 * One filesystem snapshot used by every directory policy.
 *
 * @property-read array<string, Filesystem\DirectoryListing> $listings
 */
final class DirectoryTree implements \Guard\Collect\Subject
{
    /**
     * @param array<string, Filesystem\DirectoryListing> $listings
     */
    public function __construct(
        /** @readonly */
        private array $listings,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'listings' => $this->listings,
            default => null,
        };
    }
}
