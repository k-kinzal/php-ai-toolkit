<?php

declare(strict_types=1);

namespace Guard\Reporting\Filtering;

use Guard\Reporting\Finding;

/**
 * Keeps the full check result visible when diagnostics are filtered or baselined.
 */
final class ReportScope
{
    /**
     * @param list<Finding> $findings all active diagnostics before display filters
     */
    public function __construct(public array $findings, public ?string $baseline = null, public int $suppressed = 0, public int $unmatched = 0)
    {
    }

    /**
     * Explains hidden and baselined diagnostics without claiming a filtered check passed.
     */
    public function note(int $shown): string
    {
        $lines = [];
        if ($shown !== count($this->findings)) {
            $lines[] = sprintf('Showing %d of %d active findings; %d hidden by display filters. Exit status includes all active findings.', $shown, count($this->findings), count($this->findings) - $shown);
        }
        if ($this->baseline !== null) {
            $lines[] = sprintf('Baseline %s: %d suppressed, %d unmatched.', $this->baseline, $this->suppressed, $this->unmatched);
            if ($this->unmatched > 0) {
                $lines[] = 'Review unmatched entries and regenerate the baseline to remove resolved diagnostics.';
            }
        }
        return implode("\n", $lines);
    }
}
