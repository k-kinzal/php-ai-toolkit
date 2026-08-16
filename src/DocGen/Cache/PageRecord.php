<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cache;

/**
 * What one page of a generated site is, and how it got there.
 *
 * Every page of a run reports one record, whether it was rendered or taken
 * from the cache, because the site is the whole set of records: what a run
 * leaves out of its records is what the next run removes from the output.
 *
 * @property-read string $path
 * @property-read string $signature
 * @property-read int $size
 * @property-read bool $rendered
 */
final class PageRecord
{
    /**
     * Creates the record of one page.
     *
     * @param string $path the site path of the page
     * @param string $signature the digest of everything the page was rendered from
     * @param int $size the byte count of the written page
     * @param bool $rendered whether this run rendered the page anew
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $signature,
        /** @readonly */
        private int $size,
        /** @readonly */
        private bool $rendered,
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
            'path' => $this->path,
            'signature' => $this->signature,
            'size' => $this->size,
            'rendered' => $this->rendered,
            default => null,
        };
    }
}
