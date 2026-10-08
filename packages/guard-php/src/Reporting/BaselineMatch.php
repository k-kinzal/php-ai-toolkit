<?php

declare(strict_types=1);

namespace Guard\Reporting;

/**
 * Current diagnostics after counted baseline entries have been consumed.
 */
final class BaselineMatch
{
    /**
     * @param list<Finding> $findings diagnostics that still need attention
     */
    public function __construct(public array $findings, public int $suppressed = 0, public int $unmatched = 0)
    {
    }
}
