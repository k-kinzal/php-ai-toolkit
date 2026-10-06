<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

use Guard\Structure\Subject;

/**
 * All parsed headings, reusable across policies with different level limits.
 */
final class HeadingList implements Subject
{
    /**
     * @param list<Heading> $headings
     */
    public function __construct(private array $headings)
    {
    }
    /**
     * @return list<Heading>
     */
    public function all(): array
    {
        return $this->headings;
    }
}
