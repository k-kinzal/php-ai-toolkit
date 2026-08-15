<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Doctest;

use function get_object_vars;

/**
 * One executable example extracted from a PHPDoc block.
 *
 * The source is "tag" for at-example blocks, "fence" for php code fences,
 * and "tag-inline" for display-only single-line at-example tags.
 *
 * @property-read ?string $description
 * @property-read string $code
 * @property-read string $source
 * @property-read int $index
 */
final class DocExample
{
    /**
     * Creates one extracted example.
     */
    public function __construct(
        /** @readonly */
        private ?string $description,
        /** @readonly */
        private string $code,
        /** @readonly */
        private string $source,
        /** @readonly */
        private int $index,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
