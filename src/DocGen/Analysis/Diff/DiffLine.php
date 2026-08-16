<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

/**
 * One line of a merged two-revision listing.
 *
 * A line keeps the number it had in each revision, so a listing can stay
 * anchored to the head numbering that every source link points at while
 * still showing where a removed line used to be.
 *
 * @property-read string $status
 * @property-read string $text
 * @property-read ?int $baseNumber
 * @property-read ?int $headNumber
 */
final class DiffLine
{
    /**
     * Creates one merged line.
     */
    public function __construct(
        /** @readonly */
        private string $status,
        /** @readonly */
        private string $text,
        /** @readonly */
        private ?int $baseNumber,
        /** @readonly */
        private ?int $headNumber,
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
            'status' => $this->status,
            'text' => $this->text,
            'baseNumber' => $this->baseNumber,
            'headNumber' => $this->headNumber,
            default => null,
        };
    }
}
