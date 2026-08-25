<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Git;

/**
 * The two revisions a diff site compares.
 *
 * A head of null means the working tree as it is on disk, so an
 * uncommitted change is documented without a commit.
 *
 * @property-read string $base
 * @property-read ?string $head
 */
final class RevisionRange
{
    /**
     * Creates one revision range.
     */
    public function __construct(
        /** @readonly */
        private string $base,
        /** @readonly */
        private ?string $head = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'base' => $this->base,
            'head' => $this->head,
            default => null,
        };
    }
}
