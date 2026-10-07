<?php

declare(strict_types=1);

namespace Guard\Policy\Comparison;

use function count;

/**
 * The outcome of matching items against ordered slots.
 *
 * @property-read list<array{0: int, 1: list<int>}> $unexpected each skipped item with the slots that could have taken it
 * @property-read list<int> $missing the required slots no item filled
 */
final class SequenceResult
{
    /**
     * @param list<array{0: int, 1: list<int>}> $unexpected
     * @param list<int> $missing
     */
    public function __construct(
        /** @readonly */
        private array $unexpected,
        /** @readonly */
        private array $missing,
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
            'unexpected' => $this->unexpected,
            'missing' => $this->missing,
            default => null,
        };
    }

    /**
     * Returns the number of findings the result produces.
     */
    public function count(): int
    {
        return count($this->unexpected) + count($this->missing);
    }
}
