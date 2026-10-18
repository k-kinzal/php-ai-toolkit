<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;
use JsonException;

/**
 * Renders a report in one output format.
 */
interface Reporter
{
    /**
     * Returns the whole output for a report, ending with a newline.
     *
     * @param bool $decorated whether the output may use terminal colours
     * @throws JsonException when a structured format cannot encode a finding
     */
    public function render(Report $report, bool $decorated): string;
}
