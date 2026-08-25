<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Reporting;

use Toolkit\LocGuard\Analysis\AnalysisResult;
use Toolkit\LocGuard\Config\ReportConfig;

/**
 * Formats a LocGuard analysis result for one output target.
 */
interface Reporter
{
    /**
     * Formats the analysis result using the configured output order.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string;
}
